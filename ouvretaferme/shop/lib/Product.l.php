<?php
namespace shop;

class ProductLib extends ProductCrud {

	public static function getByDate(Date $eDate, \selling\Sale $eSaleExclude = new \selling\Sale()): \Collection {

		$cProduct = Product::model()
			->select(Product::getSelection())
			->whereDate($eDate)
			->getCollection(NULL, NULL, 'product')
			->sort(['product' => ['name']], natural: TRUE);

		self::putSold($eDate, $cProduct, $eSaleExclude);

		return $cProduct;

	}

	public static function applyDiscount(\Collection $cProduct, int $discount): void {

		if($discount === 0) {
			return;
		}

		foreach($cProduct as $eProduct) {
			$eProduct['product']['privatePrice'] = round($eProduct['product']['privatePrice'] * (1 - $discount / 100), 2);
		}

	}

	public static function putSold(Date $eDate, \Collection $cProduct, \selling\Sale $eSaleExclude = new \selling\Sale()): \Collection {

		$cItem = SaleLib::getProductsByDate($eDate, $eSaleExclude);

		foreach($cProduct as $eProduct) {

			$productId = $eProduct['product']['id'];

			if($cItem->offsetExists($productId) === FALSE) {
				$sold = 0.0;
			} else {
				$sold = round($cItem[$productId]['quantity'], 2);
			}

			$eProduct['sold'] = $sold;

		}

		return $cProduct;

	}

	public static function prepareSeveral(Date $eDate, \Collection $cProductSelling, array $products, array $stocks): \Collection {

		$eDate->expects(['shop']);

		$cProduct = new \Collection();

		foreach($products as $product) {

			$product = (int)$product;

			if($cProductSelling->offsetExists($product) === FALSE) {
				continue;
			}

			if(
				array_key_exists($product, $stocks) and
				$stocks[$product] !== ''
			) {
				$stock = (float)($stocks[$product]);
			} else {
				$stock = NULL;
			}

			$eProduct = new Product([
				'date' => $eDate,
				'shop' => $eDate['shop'],
				'product' => $cProductSelling->offsetGet($product),
				'stock' => $stock,
			]);

			$cProduct->append($eProduct);

		}

		return $cProduct;

	}

	public static function addSeveral(\Collection $cProduct): void {

		Product::model()
			->option('add-ignore')
			->insert($cProduct);

	}

	public static function delete(Product $eProduct): void {

		$eProduct->expects(['id', 'shop']);

		$cSale = \selling\Sale::model()
			->select('id')
			->whereFrom(\selling\Sale::SHOP)
			->whereShopDate($eProduct['date'])
			->getCollection();

		if($cSale->notEmpty()) {
			$hasItems = \selling\Item::model()
				->whereProduct($eProduct['product'])
				->whereSale('in', $cSale)
				->count() > 0;
		} else {
			$hasItems = FALSE;
		}

		if($hasItems) {
			throw new \NotExpectedAction('This product has already been sold.');
		}

		Product::model()->beginTransaction();

		Product::model()->delete($eProduct);

		Product::model()->commit();

	}
}
?>