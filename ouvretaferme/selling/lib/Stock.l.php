<?php
namespace selling;

class StockLib extends StockCrud {

	public static function getProductsByFarm(\farm\Farm $eFarm, \Search $search = new \Search()): \Collection {

		$search->set('stock', TRUE);

		Product::model()->select([
			'stockExpired' => new \Sql('stockUpdatedAt < NOW() - INTERVAL 7 DAY', 'bool')
		]);

		return \selling\ProductLib::getByFarm($eFarm, search: $search);

	}

	public static function set(Product $eProduct, float $value, ?string $comment = NULL): void {
		self::write($eProduct, $value, $comment);
	}

	public static function increment(Product $eProduct, float $value, ?string $comment = NULL): void {

		if($value === 0.0) {
			return;
		}

		self::write($eProduct, new \Sql('stock + '.$value), $comment);

	}

	public static function decrement(Product $eProduct, float $value, ?string $comment = NULL): void {

		if($value === 0.0) {
			return;
		}

		self::write($eProduct, new \Sql('stock - '.$value), $comment);
	}

	public static function write(Product $eProduct, mixed $sql, ?string $comment): void {

		$eProduct->expects(['farm']);

		Product::model()->beginTransaction();

		Product::model()->update($eProduct, [
			'stock' => $sql,
			'stockUpdatedAt' => new \Sql('NOW()')
		]);

		$newValue = Product::model()
			->whereId($eProduct)
			->getValue('stock');

		$eStock = new Stock([
			'product' => $eProduct,
			'farm' => $eProduct['farm'],
			'newValue' => $newValue,
			'comment' => $comment
		]);

		Stock::model()->insert($eStock);

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
