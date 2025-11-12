<?php
namespace selling;

class StockLib extends StockCrud {

	public static function getByProduct(Product $eProduct): \Collection {

		return Stock::model()
			->select(Stock::getSelection() + [
				'createdBy' => [
					'firstName', 'lastName', 'vignette'
				],
			])
			->whereProduct($eProduct)
			->sort([
				'id' => SORT_DESC
			])
			->getCollection(0, 20);

	}

	public static function getProductsByFarm(\farm\Farm $eFarm, \Search $search = new \Search()): \Collection {

		$search->set('stock', TRUE);
		$search->defaultSort(new \Sql('stock = 0.0, name ASC'));

		Product::model()
			->select([
				'stockExpired' => new \Sql('stockUpdatedAt IS NOT NULL AND stockUpdatedAt < NOW() - INTERVAL 7 DAY', 'bool'),
				'stockLast' => [
					'delta', 'comment',
					'createdAt',
					'createdBy' => ['firstName', 'lastName', 'vignette']
				],
				'last' => Stock::model()
					->select([
						'minus' => new \Sql('MAX(IF(delta < 0, DATE(createdAt), null))'),
						'plus' => new \Sql('MAX(IF(delta > 0, DATE(createdAt), null))')
					])
					->group('product')
					->delegateElement('product')
		]);


		$cProduct = \selling\ProductLib::getByFarm($eFarm, search: $search);

		// Association des produits avec même nom, variété, calibre mais avec unité différente
		$ccccProduct = Product::model()
			->select([
				'id',
				'name', 'unprocessedPlant', 'unprocessedVariety', 'origin',
				'unit' => \selling\Unit::getSelection(),
			])
			->whereFarm($eFarm)
			->whereId('NOT IN', $cProduct)
			->whereStock(NULL)
			->getCollection(index: ['unprocessedPlant', 'name', 'unprocessedVariety', NULL]);

		foreach($cProduct as $eProduct) {
			$plant = $eProduct['unprocessedPlant']->empty() ? NULL : $eProduct['unprocessedPlant']['id'];
			$eProduct['cProductSiblings'] = $ccccProduct[$plant][$eProduct['name']][$eProduct['unprocessedVariety']] ?? new \Collection();
		}

		return $cProduct;

	}

	public static function getCompatibleProducts(\series\Task $eTask): \Collection {

		$eTask->expects([
			'cultivation',
			'plant', 'variety', 'harvestUnit', 'harvestSize'
		]);

		$unit = $eTask['harvestUnit'] ?? ($eTask['cultivation']->notEmpty() ? $eTask['cultivation']['mainUnit'] : NULL);

		if(
			$eTask['plant']->empty() or
			$unit === NULL
		) {
			return new \Collection();
		}

		if($eTask['variety']->notEmpty()) {
			$eTask['variety']->expects(['name']);
		}

		$eUnit = UnitLib::getByFqn($unit);

		return Product::model()
			->select(ProductElement::getSelection() + [
				'unit' => \selling\Unit::getSelection(),
			])
			->whereUnprocessedPlant($eTask['plant'])
			->whereUnit($eUnit)
			->whereStock('!=', NULL)
			->sort(new \Sql('
				'.($eTask['variety']->notEmpty() ? 'IF(unprocessedVariety = "'.Product::model()->format($eTask['variety']['name']).'", 1, 0) DESC,' : '').' 
				name ASC
			'))
			->getCollection();

	}

	public static function set(Product $eProduct, Stock $eStock): void {
		self::write($eProduct, new \Sql($eStock['newValue']), $eStock['comment']);
	}

	public static function increment(Product $eProduct, Stock $eStock, bool $zeroIfNegative = FALSE): void {

		if($eStock['newValue'] === 0.0) {
			return;
		}

		self::write($eProduct, new \Sql('stock + '.$eStock['newValue']), $eStock['comment'], $zeroIfNegative);

	}

	public static function decrement(Product $eProduct, Stock $eStock, bool $zeroIfNegative = FALSE): void {

		if($eStock['newValue'] === 0.0) {
			return;
		}

		self::write($eProduct, new \Sql('stock - '.$eStock['newValue']), $eStock['comment'], $zeroIfNegative);
	}

	protected static function write(Product $eProduct, mixed $sqlValue, ?string $comment, bool $zeroIfNegative = FALSE): void {

		$eProduct->expects(['farm']);

		Product::model()->beginTransaction();

		$eProductCalculated = Product::model()
			->select([
				'stock',
				'newStock' => $sqlValue
			])
			->whereId($eProduct)
			->get();

		if($eProductCalculated['newStock'] < 0) {

			if($zeroIfNegative) {
				$eProductCalculated['newStock'] = 0;
			} else {
				Stock::fail('newValue.negative');
				return;
			}

		}

		$eStock = new Stock([
			'product' => $eProduct,
			'farm' => $eProduct['farm'],
			'newValue' => $eProductCalculated['newStock'],
			'delta' => $eProductCalculated['newStock'] - $eProductCalculated['stock'],
			'comment' => $comment
		]);

		Stock::model()->insert($eStock);

		Product::model()->update($eProduct, [
			'stockLast' => $eStock,
			'stock' => $eProductCalculated['newStock'],
			'stockUpdatedAt' => new \Sql('NOW()')
		]);

		Product::model()->commit();

	}

	public static function enable(Product $eProduct) {

		$eProduct->expects(['farm']);

		Product::model()->beginTransaction();

		Product::model()->update($eProduct, [
			'stock' => 0,
			'stockUpdatedAt' => new \Sql('NOW()')
		]);

		\farm\Farm::model()->update($eProduct['farm'], [
			'featureStock' => TRUE
		]);

		Product::model()->commit();

	}

	public static function disable(Product $eProduct) {

		Product::model()->beginTransaction();

		Product::model()->update($eProduct, [
			'stock' => NULL,
			'stockLast' => NULL,
			'stockUpdatedAt' => NULL
		]);

		self::deleteByProduct($eProduct);

		if(
			Product::model()
				->whereFarm($eProduct['farm'])
				->whereStock('!=', NULL)
				->count() === 0
		) {

			\farm\Farm::model()->update($eProduct['farm'], [
				'featureStock' => FALSE
			]);

		}

		Product::model()->commit();

	}

	public static function getBookmarksByFarm(\farm\Farm $eFarm): \Collection {

		return StockBookmark::model()
			->select([
				'product',
				'number' => new \Sql('COUNT(*)', 'int')
			])
			->whereFarm($eFarm)
			->group('product')
			->getCollection(index: 'product');

	}

	public static function getBookmarksByProduct(Product $eProduct): \Collection {

		return StockBookmark::model()
			->select(StockBookmark::getSelection() + [
				'plant' => ['name', 'fqn', 'vignette'],
				'variety' => ['name'],
				'createdBy' => ['vignette', 'firstName', 'lastName']
			])
			->whereProduct($eProduct)
			->sort('id')
			->getCollection();

	}

	public static function deleteByProduct(Product $eProduct): void {

		$eProduct->expects(['farm']);

		Stock::model()->beginTransaction();

			StockBookmarkLib::deleteByProduct($eProduct);

			Stock::model()
				->whereFarm($eProduct['farm'])
				->whereProduct($eProduct)
				->delete();

		Stock::model()->commit();

	}

	public static function getBookmark(\series\Task $eTask): Product {

		$eTask->expects(['farm', 'plant', 'harvestUnit', 'variety', 'harvestSize', 'cultivation']);

		if($eTask['harvestUnit'] === NULL and $eTask['cultivation']->notEmpty()) {
			$unit = $eTask['cultivation']['mainUnit'];
		} else {
			$unit = $eTask['harvestUnit'];
		}

		$eStockBookmark = StockBookmark::model()
			->select('product')
			->wherePlant($eTask['plant'])
			->whereUnit($unit)
			->whereVariety($eTask['variety'])
			->get();

		if($eStockBookmark->empty()) {
			return new Product();
		} else {
			return $eStockBookmark['product'];
		}

	}

	public static function remember(\series\Task $eTask, Product $eProduct): void {

		$eTask->expects(['farm', 'plant', 'harvestUnit', 'variety', 'harvestSize']);

		StockBookmark::model()->beginTransaction();

			self::forget($eTask);

			if($eProduct->notEmpty()) {

				$eStockBookmark = new StockBookmark([
					'farm' => $eTask['farm'],
					'plant' => $eTask['plant'],
					'unit' => $eTask['harvestUnit'],
					'variety' => $eTask['variety'],
					'size' => $eTask['harvestSize'],
					'product' => $eProduct,
				]);

				StockBookmark::model()->insert($eStockBookmark);

			}

		StockBookmark::model()->commit();

	}

	public static function forget(\series\Task $eTask): void {

		$eTask->expects(['plant', 'harvestUnit', 'variety', 'harvestSize']);

		StockBookmark::model()
			->wherePlant($eTask['plant'])
			->whereUnit($eTask['harvestUnit'])
			->whereVariety($eTask['variety'])
			->delete();

	}

}
?>
