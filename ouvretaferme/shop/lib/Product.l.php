<?php
namespace shop;

class ProductLib extends ProductCrud {

	public static function getByDate(Date $eDate, bool $onlyActive = TRUE, \selling\Sale $eSaleExclude = new \selling\Sale()): \Collection {

		$cProduct = Product::model()
			->select(Product::getSelection())
			->whereDate($eDate)
			->whereStatus(Product::ACTIVE, if: $onlyActive)
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
			$eProduct['price'] = round($eProduct['price'] * (1 - $discount / 100), 2);
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

	public static function prepareSeveral(Date $eDate, \Collection $cProductSelling, array $products, array $input): \Collection {

		$eDate->expects(['shop']);

		$cProduct = new \Collection();

		foreach($products as $index => $product) {

			$product = (int)$product;

			if($cProductSelling->offsetExists($product) === FALSE) {
				continue;
			}

			$eProduct = new Product([
				'date' => $eDate,
				'shop' => $eDate['shop'],
				'product' => $cProductSelling->offsetGet($product)
			]);

			$eProduct->buildIndex(['stock', 'price'], $input, $index);

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