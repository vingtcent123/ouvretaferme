<?php
namespace selling;

class StockBookmarkLib extends StockBookmarkCrud {

	public static function deleteByProduct(Product $eProduct): void {

		$eProduct->expects(['farm']);

		StockBookmark::model()
			->whereFarm($eProduct['farm'])
			->whereProduct($eProduct)
			->delete();

	}

}
?>
