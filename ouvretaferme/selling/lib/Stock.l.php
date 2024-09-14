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
		$cccccProduct = Product::model()
			->select([
				'id',
				'plant', 'name', 'variety', 'size', 'unit'
			])
			->whereFarm($eFarm)
			->whereId('NOT IN', $cProduct)
			->whereStock(NULL)
			->getCollection(index: ['plant', 'name', 'variety', 'size', NULL]);

		foreach($cProduct as $eProduct) {
			$eProduct['cProductSiblings'] = $cccccProduct[$eProduct['plant']['id']][$eProduct['name']][$eProduct['variety']][$eProduct['size']] ?? new \Collection();
		}

		return $cProduct;

	}

	public static function getCompatibleProducts(\series\Task $eTask): \Collection {

		$eTask->expects([
			'cultivation' => ['mainUnit'],
			'plant', 'variety', 'harvestUnit', 'harvestSize'
		]);

		$unit = $eTask['harvestUnit'] ?? $eTask['cultivation']['mainUnit'];

		if(
			$eTask['plant']->empty() or
			$unit === NULL
		) {
			return new \Collection();
		}

		if($eTask['variety']->notEmpty()) {
			$eTask['variety']->expects(['name']);
		}

		if($eTask['harvestSize']->notEmpty()) {
			$eTask['harvestSize']->expects(['name']);
		}

		return Product::model()
			->select([
				'id', 'name', 'unit', 'variety', 'size',
				'vignette'
			])
			->wherePlant($eTask['plant'])
			->whereUnit($unit)
			->whereStock('!=', NULL)
			->sort(new \Sql('
				'.($eTask['variety']->notEmpty() ? 'IF(variety = "'.Product::model()->format($eTask['variety']['name']).'", 1, 0)' : '0').'
					+ '.($eTask['harvestSize']->notEmpty() ? 'IF(size = "'.Product::model()->format($eTask['harvestSize']['name']).'", 1, 0)' : '0   ').'
					DESC,
				name ASC
			'))
			->getCollection();

	}

	public static function set(Product $eProduct, Stock $eStock): void {
		self::write($eProduct, new \Sql($eStock['newValue']), $eStock['comment']);
	}

	public static function increment(Product $eProduct, Stock $eStock): void {

		if($eStock['newValue'] === 0.0) {
			return;
		}

		self::write($eProduct, new \Sql('stock + '.$eStock['newValue']), $eStock['comment']);

	}

	public static function decrement(Product $eProduct, Stock $eStock): void {

		if($eStock['newValue'] === 0.0) {
			return;
		}

		self::write($eProduct, new \Sql('stock - '.$eStock['newValue']), $eStock['comment']);
	}

	protected static function write(Product $eProduct, mixed $sqlValue, ?string $comment): void {

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
			Stock::fail('newValue.negative');
			return;
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

		Stock::model()
			->whereProduct($eProduct)
			->delete();

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

}
?>
