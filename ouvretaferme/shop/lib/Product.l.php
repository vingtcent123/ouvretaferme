<?php
namespace shop;

class ProductLib extends ProductCrud {

	public static function getPropertiesUpdate(): \Closure {

		return function(Product $eProduct) {

			$properties = ['price', 'available', 'limitCustomers', 'limitNumber'];

			if($eProduct['catalog']->notEmpty()) {
				$properties[] = 'limitStartAt';
				$properties[] = 'limitEndAt';
			}

			return $properties;

		};

	}

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
			$ccProduct = self::getByDate($eDate);
			// Multi producteur pas géré
			return $ccProduct->empty() ? new Collection() : $ccProduct->first();
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

	public static function excludeExisting(Date|Catalog $e, \Collection $cProductSelling): void {

		if($e instanceof Date) {
			$cProduct = self::getColumnByDate($e, 'product');
		} else {
			$cProduct = self::getColumnByCatalog($e, 'product');
		}

		if($cProduct === []) {
			return;
		}

		foreach($cProduct as $eProduct) {
			$cProductSelling->offsetUnset($eProduct['id']);
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
				'farm',
				'packaging',
				'price' => new \Sql('SUM(price) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'sold' => new \Sql('SUM(number)', 'float'),
			])
			->where('sale', 'IN', $cSale)
			->where('product', 'NOT IN', $cProductExclude)
			->where('number > 0')
			->group(['farm', 'product', 'packaging'])
			->getCollection();

		$ccProduct = new \Collection();

		foreach($cItem as $eItem) {

			$ccProduct[$eItem['farm']['id']] ??= new \Collection();

			$ccProduct[$eItem['farm']['id']][] = new Product([
				'farm' => $eItem['farm'],
				'product' => $eItem['product'],
				'packaging' => $eItem['packaging'],
				'price' => round($eItem['price'], 2),
				'sold' => round($eItem['sold'], 2),
				'catalog' => new Catalog(),
				'date' => new Date(),
				'limitNumber' => NULL,
				'limitCustomers' => [],
				'limitStartAt' => NULL,
				'limitEndAt' => NULL,
				'available' => NULL,
				'status' => Product::INACTIVE,
			]);

		}

		return $ccProduct;

	}

	public static function getColumnByDate(Date $eDate, string $column, ?\Closure $apply = NULL): array|\Collection {

		if($apply) {
			$apply(Product::model());
		}

		$data = Product::model()
			->select($column)
			->whereDate($eDate)
			->getColumn($column);

		if($eDate->isCatalog()) {

			if($apply) {
				$apply(Product::model());
			}

			$newData = Product::model()
				->select(ProductElement::getSelection())
				->whereCatalog('IN', $eDate['catalogs'])
				->getColumn($column);

			if($newData instanceof \Collection) {
				$data->mergeCollection($newData);
			} else {
				$data = array_merge($data, $newData);
			}

		}

		return $data;

	}

	public static function getByDate(Date $eDate, \selling\Customer $eCustomer = new \selling\Customer(), \selling\Sale $eSaleExclude = new \selling\Sale()): \Collection {

		$ids = self::getColumnByDate($eDate, 'id', function(ProductModel $m) use ($eDate, $eCustomer) {

			$m
				->whereStatus(Product::ACTIVE, if: $eCustomer->notEmpty())
				->where(fn() => 'JSON_LENGTH(limitCustomers) = 0 OR JSON_CONTAINS(limitCustomers, \''.$eCustomer['id'].'\')', if: $eCustomer->notEmpty())
				->where('limitStartAt IS NULL OR '.$m->format($eDate['deliveryDate']).' >= limitStartAt')
				->where('limitEndAt IS NULL OR '.$m->format($eDate['deliveryDate']).' <= limitEndAt');

		});

		if($ids === []) {
			return new \Collection();
		}

		$ccProduct = Product::model()
			->select(Product::getSelection())
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, ['farm', 'product']);

		$ccProduct->map(function($cProduct) use ($eDate, $eSaleExclude) {

			$cProduct->sort(['product' => ['name']], natural: TRUE);
			self::putSold($eDate, $cProduct, $eSaleExclude);

		});


		return $ccProduct;

	}

	public static function getColumnByCatalog(Catalog $eCatalog, string $column): array|\Collection {

		return Product::model()
			->select($column)
			->whereCatalog($eCatalog)
			->getColumn($column);

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

			if($e instanceof Catalog) {
				CatalogLib::recalculate($e);
			}


		Product::model()->commit();

	}

	public static function delete(Product $eProduct): void {

		$eProduct->expects(['id', 'catalog']);

		Product::model()->beginTransaction();

			Product::model()->delete($eProduct);

			if($eProduct['catalog']->notEmpty()) {
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

			$number += $eProduct['available'];

			return ($eProduct['limitNumber'] === NULL) ?
				$number :
				min($eProduct['limitNumber'], $number);

		}  else {

			return ($eProduct['limitNumber'] === NULL) ?
				NULL :
				$eProduct['limitNumber'];

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