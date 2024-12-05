<?php
namespace shop;

class ProductLib extends ProductCrud {

	public static function getForCopy(Shop $eShop, Date $eDate): \Collection {

		$eShop->expects(['type']);

		if($eDate->isDirect() === FALSE) {
			return new \Collection();
		}

		// La boutique a changé de grille tarifaire par rapport à la date copiée
		if($eShop['type'] !== $eDate['type']) {

			$cProductSelling = Product::model()
				->whereDate($eDate)
				->getColumn('product');

			$cProductShop = new \Collection();

			foreach($cProductSelling as $eProductSelling) {

				$cProductShop[] = new Product([
					'product' => $eProductSelling,
					'price' => NULL
				]);

			}

			return $cProductShop;

		} else {
			return self::getByDate($eDate, FALSE);
		}

	}

	public static function countCatalogsByFarm(\farm\Farm $eFarm): array {

		return Product::model()
			->select([
				'catalog',
				'count' => new \Sql('COUNT(*)', 'int')
			])
			->whereFarm($eFarm)
			->whereCatalog('!=', NULL)
			->group('catalog')
			->getCollection()
			->toArray(fn($eProduct) => [$eProduct['catalog']['id'], $eProduct['count']], TRUE);

	}

	public static function countByCatalog(Catalog $eCatalog): int {

		return Product::model()
			->whereCatalog($eCatalog)
			->count();

	}

	public static function countByDate(Date $eDate): int {

		$eDate->expects(['catalogs']);

		return Product::model()
			->whereCatalog('IN', $eDate['catalogs'], if: $eDate->isCatalog())
			->whereDate($eDate, if: $eDate->isDirect())
			->count();

	}

	public static function excludeExisting(Date|Catalog $e, \Collection $cProductSelling): void {

		if($e instanceof Date) {
			$cProduct = self::getByDate($e, onlyActive: FALSE);
		} else {
			$cProduct = self::getByCatalog($e, onlyActive: FALSE);
		}

		foreach($cProduct as $eProduct) {
			$cProductSelling->offsetUnset($eProduct['product']['id']);
		}

	}

	public static function aggregateBySales(\Collection $cSale, \Collection $cProductExclude = new \Collection()): \Collection {

		$cItem = \selling\Item::model()
			->select([
				'product' => [
					'name', 'vignette', 'unit', 'category', 'variety', 'quality', 'size',
					'stock', 'stockUpdatedAt',
					'stockExpired' => new \Sql('stockUpdatedAt IS NOT NULL AND stockUpdatedAt < NOW() - INTERVAL 7 DAY', 'bool'),
					'plant' => ['name', 'fqn', 'vignette']
				],
				'packaging',
				'price' => new \Sql('SUM(price) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'sold' => new \Sql('SUM(number)', 'float'),
			])
			->where('sale', 'IN', $cSale)
			->where('product', 'NOT IN', $cProductExclude)
			->where('number > 0')
			->group(['product', 'packaging'])
			->getCollection();

		$cProduct = new \Collection();

		foreach($cItem as $eItem) {

			$cProduct[] = new Product([
				'product' => $eItem['product'],
				'packaging' => $eItem['packaging'],
				'price' => round($eItem['price'], 2),
				'sold' => round($eItem['sold'], 2),
				'catalog' => new Catalog(),
				'date' => new Date(),
				'saleStartAt' => NULL,
				'saleEndAt' => NULL,
				'available' => NULL,
				'status' => Product::INACTIVE,
			]);

		}

		return $cProduct;

	}

	public static function getByDate(Date $eDate, bool $onlyActive = TRUE, \selling\Sale $eSaleExclude = new \selling\Sale()): \Collection {

		$cProduct = Product::model()
			->select(Product::getSelection())
			->whereCatalog('IN', $eDate['catalogs'], if: $eDate->isCatalog())
			->whereDate($eDate, if: $eDate->isDirect())
			->whereStatus(Product::ACTIVE, if: $onlyActive)
			->getCollection(NULL, NULL, 'product')
			->sort(['product' => ['name']], natural: TRUE);

		self::putSold($eDate, $cProduct, $eSaleExclude);

		return $cProduct;

	}

	public static function getByCatalog(Catalog $eCatalog, bool $onlyActive = TRUE): \Collection {

		$cProduct = Product::model()
			->select(Product::getSelection())
			->whereCatalog($eCatalog)
			->whereStatus(Product::ACTIVE, if: $onlyActive)
			->getCollection(NULL, NULL, 'product')
			->sort(['product' => ['name']], natural: TRUE);

		$cProduct->setColumn('sold', NULL);

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

	public static function prepareCollection(Date|Catalog $e, \Collection $cProductSelling, array $products, array $input): \Collection {

		if($e instanceof Date) {

			if($e->isDirect() === FALSE) {
				throw new \Exception('Invalid source');
			}

			$e->expects([
				'shop',
				'type'
			]);

			$base = [
				'date' => $e,
				'shop' => $e['shop'],
				'type' => $e['type'],
				'farm' => $e['farm'],
			];

		} else {

			$base = [
				'catalog' => $e,
				'type' => $e['type'],
				'farm' => $e['farm'],
			];

		}

		$cProduct = new \Collection();

		foreach($products as $index => $product) {

			$product = (int)$product;

			if($cProductSelling->offsetExists($product) === FALSE) {
				continue;
			}

			$eProductSelling = $cProductSelling->offsetGet($product);

			$eProduct = new Product([
				'product' => $eProductSelling,
				'packaging' => ($base['type'] === Product::PRO) ? $eProductSelling['proPackaging'] : NULL,
			] + $base);

			$eProduct->buildIndex(['available', 'price'], $input, $index);

			$cProduct->append($eProduct);

		}

		if($cProduct->empty()) {
			Product::fail('empty');
		}

		return $cProduct;

	}

	public static function create(Product $e): void {
		throw new \Exception('Not implemented');
	}

	public static function createCollection(Date|Catalog $e, \Collection $c): void {

		if($c->empty()) {
			return;
		}

		Product::model()->beginTransaction();

			Product::model()
				->option('add-ignore')
				->insert($c);

			if($e instanceof Date) {
				DateLib::recalculate($e);
			} else {
				CatalogLib::recalculate($e);
			}


		Product::model()->commit();

	}

	public static function delete(Product $eProduct): void {

		$eProduct->expects(['id', 'date', 'shop', 'catalog']);

		if($eProduct['date']->notEmpty()) {

			$cSale = \selling\Sale::model()
				->select('id')
				->whereFrom(\selling\Sale::SHOP)
				->whereShopDate($eProduct['date'])
				->getCollection();

			if($cSale->notEmpty()) {
				$hasItems = \selling\Item::model()
					->whereProduct($eProduct['product'])
					->whereSale('in', $cSale)
					->exists();
			} else {
				$hasItems = FALSE;
			}

			if($hasItems) {
				throw new \NotExpectedAction('This product has already been sold.');
			}

		}

		Product::model()->beginTransaction();

			Product::model()->delete($eProduct);

			if($eProduct['date']->notEmpty()) {
				DateLib::recalculate($eProduct['date']);
			} else {
				CatalogLib::recalculate($eProduct['catalog']);
			}

		Product::model()->commit();

	}

	public static function getReallyAvailable(Product $eProduct, \selling\Product $eProductSelling, \selling\Sale $eSale): ?float {

		if($eSale->exists()) {

			$eSale->expects(['cItem']);

			$eItem = $eSale['cItem']->find(fn($eItem) => $eItem['product']['id'] === $eProductSelling['id'], limit: 1);
			$number = $eItem->empty() ? 0 : $eItem['number'];
		} else {
			$number = 0;
		}

		if($eProduct['available'] !== NULL) {
			return $eProduct['available'] + $number;
		}  else {
			return NULL;
		}

	}

	public static function addAvailable(\Collection $cItem): void {
		self::setAvailable($cItem, '+');
	}

	public static function removeAvailable(\Collection $cItem): void {
		self::setAvailable($cItem, '-');
	}

	private static function setAvailable(\Collection $cItem, string $sign): void {

		$cItem->expects([
			'shopProduct',
			'number'
		]);

		foreach($cItem as $eItem) {

			$eProduct = $eItem['shopProduct'];

			if($eProduct->notEmpty()) {

				Product::model()
					->whereAvailable('!=', NULL)
					->update($eProduct, [
						'available' => new \Sql('available '.$sign.' '.$eItem['number'])
					]);

			}

		}

	}

}
?>