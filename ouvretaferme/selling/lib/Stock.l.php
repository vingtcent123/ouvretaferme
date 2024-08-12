<?php
namespace selling;

class StockLib extends StockCrud {

	public static function getProductsByFarm(\farm\Farm $eFarm, \Search $search = new \Search()) {

		$search->set('stock', TRUE);

		return \selling\ProductLib::getByFarm($eFarm, search: $search);

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
