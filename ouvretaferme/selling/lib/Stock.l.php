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
				'stockExpired' => new \Sql('stockUpdatedAt < NOW() - INTERVAL 7 DAY', 'bool'),
				'stockLast' => [
					'delta',
					'createdAt',
					'createdBy' => ['firstName', 'lastName', 'vignette']
				]
			]);


		return \selling\ProductLib::getByFarm($eFarm, search: $search);

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
