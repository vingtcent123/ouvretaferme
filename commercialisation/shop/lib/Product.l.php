<?php
namespace shop;

class ProductLib extends ProductCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Product $eProduct) {

			$eProduct->expects(['parent']);

			if($eProduct['parent']) {
				return ['parentName', 'children'];
			} else {
				throw new \UnsupportedException();
			}

		};

	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Product $eProduct) {

			if($eProduct['parent']) {
				return ['parentName', 'children'];
			}

			$properties = ['promotion', 'price', 'priceDiscount', 'available', 'limitGroups', 'excludeGroups', 'limitCustomers', 'excludeCustomers', 'limitMin', 'limitMax'];

			if($eProduct['type'] === Product::PRO) {
				$properties[] = 'packaging';
			}

			if($eProduct['catalog']->notEmpty()) {
				$properties[] = 'limitStartAt';
				$properties[] = 'limitEndAt';
			}

			return $properties;

		};

	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, Catalog $eCatalog, Date $eDate, bool $withRelations): \Collection {

		if($eCatalog->notEmpty()) {

			$cProductExclude = $withRelations ?
				new \Collection() :
				Relation::model()
					->select('child')
					->whereCatalog($eCatalog)
					->getColumn('child');

			Product::model()->whereCatalog($eCatalog);

		} else if($eDate->notEmpty()) {

			$cProductExclude = $withRelations ?
				new \Collection() :
				Relation::model()
					->select('child')
					->whereDate($eDate)
					->getColumn('child');

			Product::model()->whereDate($eDate);

		} else {
			throw new \Exception('Missing catalog or date');
		}

		if($withRelations === FALSE) {

			Product::model()
				->whereId('NOT IN', $cProductExclude, if: $cProductExclude->notEmpty())
				->whereParent(FALSE);

		}

		$cProduct = Product::model()
			->select(Product::getSelection())
			->getCollection(index: 'product');

		if($cProduct->empty()) {
			return new \Collection();
		}

		$onlyIds = $cProduct
			->getColumnCollection('product')
			->getIds();

		$filteredIds = \selling\ProductLib::getFromQuery($query, $eFarm, onlyIds: $onlyIds, withComposition: FALSE, properties: ['id'])->getIds();

		$cProductFiltered = new \Collection();

		foreach($filteredIds as $id) {
			$cProductFiltered[] = $cProduct[$id];
		}

		return $cProductFiltered;

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
			return self::getByDate($eDate);
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
			$cProduct = self::getColumnByDate($e, 'product', withParent: FALSE);
		} else {
			$cProduct = self::getColumnByCatalog($e, 'product', withParent: FALSE);
		}

		if($cProduct === []) {
			return;
		}

		foreach($cProduct as $eProduct) {
			if($eProduct->exists()) {
				$cProductSelling->offsetUnset($eProduct['id']);
			}
		}

	}

	public static function aggregateBySales(\Collection $cSale, \Collection $cProductExclude = new \Collection()): \Collection {

		$cProductSellingExclude = new \Collection();

		foreach($cProductExclude as $eProductExclude) {

			$cProductSellingExclude[] = $eProductExclude['product'];

			if($eProductExclude['parent']) {
				$cProductSellingExclude->mergeCollection($eProductExclude['cProductChild']->getColumnCollection('product'));
			}

		}

		$cItem = \selling\Item::model()
			->select([
				'product' => \selling\ProductElement::getSelection() + [
					'unit' => \selling\Unit::getSelection(),
					'stockExpired' => new \Sql('stockUpdatedAt IS NOT NULL AND stockUpdatedAt < NOW() - INTERVAL 7 DAY', 'bool'),
					'unprocessedPlant' => ['name', 'fqn', 'vignette']
				],
				'farm',
				'packaging',
				'price' => new \Sql('SUM(price) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'sold' => new \Sql('SUM(number)', 'float'),
			])
			->where('sale', 'IN', $cSale)
			->where('product', 'NOT IN', $cProductSellingExclude)
			->where('number > 0')
			->whereIngredientOf(NULL)
			->whereStatus('NOT IN', [\selling\Sale::EXPIRED, \selling\Sale::CANCELED])
			->group(['farm', 'product', 'packaging'])
			->getCollection();

		$cProduct = new \Collection();

		foreach($cItem as $eItem) {

			$cProduct[] = new Product([
				'farm' => $eItem['farm'],
				'product' => $eItem['product'],
				'parent' => FALSE,
				'packaging' => $eItem['packaging'],
				'price' => round($eItem['price'], 2),
				'priceInitial' => NULL,
				'sold' => round($eItem['sold'], 2),
				'catalog' => new Catalog(),
				'date' => new Date(),
				'promotion' => Product::NONE,
				'limitMax' => NULL,
				'limitMin' => NULL,
				'limitCustomers' => [],
				'limitGroups' => [],
				'limitStartAt' => NULL,
				'limitEndAt' => NULL,
				'excludeCustomers' => NULL,
				'excludeGroups' => NULL,
				'available' => NULL,
				'status' => Product::INACTIVE,
			]);

		}

		return $cProduct;

	}

	public static function getColumnByDate(Date $eDate, string $column, ?\Closure $apply = NULL, bool $withParent = TRUE): array|\Collection {

		if($apply) {
			$apply(Product::model());
		}

		$data = Product::model()
			->select($column)
			->whereDate($eDate)
			->whereParent(NULL, if: $withParent === FALSE)
			->getColumn($column);

		if($eDate->isCatalog()) {

			if($apply) {
				$apply(Product::model());
			}

			$newData = Product::model()
				->select(ProductElement::getSelection())
				->whereCatalog('IN', $eDate['catalogs'])
				->whereParent(NULL, if: $withParent === FALSE)
				->getColumn($column);

			if($newData instanceof \Collection) {
				$data->mergeCollection($newData);
			} else {
				$data = array_merge($data, $newData);
			}

		}

		return $data;

	}

	public static function getByDate(Date $eDate, \selling\Customer $eCustomer = new \selling\Customer(), \Collection $cSaleExclude = new \Collection(), bool $withIngredients = FALSE, bool $public = FALSE, bool $withParents = TRUE, bool $reorderChildren = FALSE): \Collection {

		$ids = self::getColumnByDate($eDate, 'id', function(ProductModel $m) use($eDate, $eCustomer, $public) {

			$referenceDate = $eDate['deliveryDate'] ?? currentDate();

			$m
				->whereStatus(Product::ACTIVE, if: $public)
				->where('limitStartAt IS NULL OR '.$m->format($referenceDate).' >= limitStartAt')
				->where('limitEndAt IS NULL OR '.$m->format($referenceDate).' <= limitEndAt');

			if($public) {

				if($eCustomer->notEmpty()) {

					$eCustomer->expects(['groups']);

					$m
						->or(
							fn() => $this->where(fn() => 'JSON_LENGTH(limitCustomers) = 0 AND JSON_LENGTH(limitGroups) = 0'),
							fn() => $this->where(fn() => 'JSON_CONTAINS(limitCustomers, \''.$eCustomer['id'].'\')'),
							fn() => $this->where(fn() => 'JSON_OVERLAPS(limitGroups, "['.implode(', ', $eCustomer['groups']).']")')
						)
						->where(fn() => 'JSON_LENGTH(excludeCustomers) = 0 OR JSON_CONTAINS(excludeCustomers, \''.$eCustomer['id'].'\') = 0')
						->where(fn() => 'JSON_LENGTH(excludeGroups) = 0 OR JSON_OVERLAPS(excludeGroups, "['.implode(', ', $eCustomer['groups']).']") = 0');

				} else {

					$m
						->where(fn() => 'JSON_LENGTH(limitCustomers) = 0')
						->where(fn() => 'JSON_LENGTH(limitGroups) = 0');

				}

			}

		});

		if($ids === []) {
			return new \Collection();
		}

		if($eCustomer->empty()) {
			$cGrid = new \Collection();
		} else {
			$cGrid = \selling\GridLib::calculateByCustomer($eCustomer);
		}

		$cProduct = Product::model()
			->select(Product::getSelection())
			->whereId('IN', $ids)
			->whereParent(FALSE, if: $withParents === FALSE)
			->getCollection(NULL, NULL, 'id');

		if($withIngredients) {

			$cProductSellingComposition = $cProduct
				->find(fn($eProduct) => (
					$eProduct['product']->notEmpty() and
					($eProduct['product']['profile'] === \selling\Product::COMPOSITION) and
					($public === FALSE or $eProduct['product']['compositionVisibility'] === \selling\Product::PUBLIC)
				), clone: FALSE)
				->getColumnCollection('product');

			if($cProductSellingComposition->notEmpty()) {

				$referenceDate = $eDate['deliveryDate'] ?? currentDate();

				\selling\Product::model()
					->select([
						'cItemIngredient' => new \selling\SaleLib()->delegateIngredients($referenceDate, 'id')
					])
					->get($cProductSellingComposition);

			}

		}

		self::applySold($eDate, $cProduct, $cGrid, $cSaleExclude);

		if($reorderChildren === FALSE) {
			$cProductOrdered = $cProduct;
		} else {
			$cProductOrdered = self::reorderChildren($cProduct, filtered: TRUE);
		}

		$order = [];

		// On place en premier les produits composés pour des raisons d'affichage
		if(
			$withIngredients and
			$eDate['type'] === Date::PRIVATE and
			$public
		) {

			$order[] = function($e1, $e2) {

				$n1 = ($e1['product']['cItemIngredient'] ?? new \Collection())->count();
				$n2 = ($e2['product']['cItemIngredient'] ?? new \Collection())->count();

				return ($n1 === $n2) ? 0 : (($n1 > $n2) ? -1 : 1);

			};

		}

		$order[] = function($e1, $e2) {
			if($e1['promotion'] !== Product::NONE) {
				return -1;
			} else if($e2['promotion'] !== Product::NONE) {
				return 1;
			} else {
				return 0;
			}
		};

		$order['product'] = ['name'];

		$cProductOrdered->sort($order, natural: TRUE);

		return $cProductOrdered;

	}

	public static function findAvailable(Shop $eShop, \Collection $cProduct, \Collection $cItemExisting): array|\Collection {

		$cProductAvailable = new \Collection();

		foreach($cProduct as $key => $eProduct) {

			if($eProduct['parent']) {

				$eProduct['cProductChild'] = self::findAvailable($eShop, $eProduct['cProductChild'], $cItemExisting);

				if($eProduct['cProductChild']->notEmpty()) {

					$cProductAvailable[] = $eProduct->merge([
						'reallyAvailable' => NULL
					]);

				}

			} else {

				$reallyAvailable = \shop\ProductLib::getReallyAvailable($eProduct, $eProduct['product'], $cItemExisting);

				if(
					($eShop['outOfStock'] === \shop\Shop::SHOW) or
					($eShop['outOfStock'] === \shop\Shop::HIDE and $reallyAvailable !== 0.0)
				) {
					$cProductAvailable[] = $eProduct->merge([
						'reallyAvailable' => $reallyAvailable
					]);
				}

			}

		}

		return $cProductAvailable;

	}

	public static function getColumnByCatalog(Catalog $eCatalog, string $column, bool $withParent = TRUE): array|\Collection {

		return Product::model()
			->select($column)
			->whereCatalog($eCatalog)
			->whereParent(NULL, if: $withParent === FALSE)
			->getColumn($column);

	}

	public static function getByCatalog(Catalog $eCatalog, bool $onlyActive = TRUE, bool $reorderChildren = FALSE): \Collection {

		$cProduct = Product::model()
			->select(Product::getSelection())
			->whereCatalog($eCatalog)
			->whereParent(FALSE, if: $reorderChildren === FALSE)
			->whereStatus(Product::ACTIVE, if: $onlyActive)
			->getCollection(NULL, NULL, 'id');

		$cProduct->setColumn('sold', NULL);

		if($reorderChildren === FALSE) {
			$cProductOrdered = $cProduct;
		} else {
			$cProductOrdered = self::reorderChildren($cProduct);
		}

		return $cProductOrdered->sort(['product' => ['name']], natural: TRUE);

	}

	/**
	 * filtered à TRUE permet d'ignorer les erreurs si l'enfant n'est pas référencé dans les produits
	 * À n'utiliser que s'il y a des recherches par filtre (= limites sur des clients...)
	 */
	public static function reorderChildren(\Collection $cProduct, bool $filtered = FALSE): \Collection {

		$cProductParent = $cProduct->find(fn($eProduct) => $eProduct['parent'] !== NULL);

		$ccRelation = Relation::model()
			->select(Relation::getSelection())
			->whereParent('IN', $cProductParent)
			->whereChild('IN', $cProduct, if: $filtered)
			->getCollection(index: ['parent', 'child']);

		$cProductChild = $ccRelation->getColumnCollection('child', 'child');

		$cProductOrdered = new \Collection();

		foreach($cProduct as $eProduct) {

			if(
				$cProductChild->offsetExists($eProduct['id']) // C'est un enfant, il va être passé dans cProductChild
			) {
				continue;
			}

			$eProduct['child'] = FALSE;

			if($eProduct['parent']) {

				if(
					$ccRelation->offsetExists($eProduct['id']) === FALSE // Tous les enfants de ce parent sont désactivés
				) {
					continue;
				}


				$eProduct['cProductChild'] = new \Collection();
				$eProduct['product'] = new \selling\Product([
					'farm' => $eProduct['farm'],
					'name' => $eProduct['parentName'],
					'category' => $eProduct['parentCategory'],
				]);

				$cProductOrdered[] = $eProduct;

				foreach($ccRelation[$eProduct['id']] as $eRelation) {

					$eProductChild = $cProduct[$eRelation['child']['id']];
					$eProductChild['child'] = TRUE;

					$eProduct['cProductChild'][] = $eProductChild;

				}

			} else {
				$cProductOrdered[] = $eProduct;
			}

		}

		return $cProductOrdered;
	}

	public static function exportAsSelling(\Collection $cProduct): \Collection {

		$cProductSelling = new \Collection();

		foreach($cProduct as $eProduct) {

			$eProductSelling = $eProduct['product'];
			$eProductSelling['shopProduct'] = $eProduct;
			$eProductSelling['packaging'] = $eProduct['packaging'];
			$eProductSelling[$eProduct['type'].'Price'] = $eProduct['price'];
			$eProductSelling[$eProduct['type'].'PriceInitial'] = $eProduct['priceInitial'];

			$cProductSelling[] = $eProductSelling;

		}

		return $cProductSelling;

	}

	public static function applySold(Date $eDate, \Collection $cProduct, \Collection $cGrid, \Collection $cSaleExclude = new \Collection()): void {

		if($eDate['deliveryDate'] === NULL) {
			return;
		}

		$cItem = SaleLib::getProductsByDate($eDate, $cSaleExclude);

		foreach($cProduct as $eProduct) {

			if($eProduct['parent']) {
				$eProduct['sold'] = NULL;
				continue;
			}

			$productId = $eProduct['product']['id'];

			if($cItem->offsetExists($productId) === FALSE) {
				$sold = 0.0;
			} else {
				$sold = round($cItem[$productId]['quantity'], 2);
			}

			self::applyGrid($eProduct, $cGrid[$eProduct['product']['id']] ?? new \selling\Grid());

			$eProduct['sold'] = $sold;

		}

	}

	public static function applyIndexing(Shop $eShop, Date $eDate, \Collection $cProduct): void {

		$eDate['productsApproximate'] = (
			$eShop->isApproximate() and
			$cProduct->contains(fn($eProduct) => (
				$eProduct['parent'] === FALSE and
				$eProduct['product']['unit']->notEmpty() and
				$eProduct['product']['unit']['approximate'])
			)
		);

		if($eShop['shared']) {

			$index = $eShop['sharedGroup'] ?? 'product';

			switch($index) {

				case 'product' :
					$eDate['cProduct'] = $cProduct;

					if($eShop['sharedCategory']) {
						$eDate['ccProduct'] = $cProduct->reindex(['product', 'category']);
					}
					break;

				case 'farm' :
					$eDate['ccProduct'] = $cProduct->reindex(['product', 'farm']);

					if($eShop['sharedCategory']) {

						$eDate['cccProduct'] = new \Collection();
						foreach($eDate['ccProduct'] as $farm => $cProductByFarm) {
							$eDate['cccProduct'][$farm] = $cProductByFarm->reindex(['product', 'category']);

						}

					}
					break;

				case 'department' :
					$eShop->expects(['ccRange']);

					$eDate['ccProduct'] = $cProduct->reindex(function($eProduct) use($eShop) {

						if($eProduct['catalog']->empty()) {
							return NULL;
						}

						$eRange = $eShop['ccRange'][$eProduct['farm']['id']][$eProduct['catalog']['id']] ?? new Range();

						if($eRange->empty() or $eRange['department']->empty()) {
							return NULL;
						} else {
							return $eRange['department']['id'];
						}
					});

					if($eShop['sharedCategory']) {

						$eDate['cccProduct'] = new \Collection();
						foreach($eDate['ccProduct'] as $farm => $cProductByFarm) {
							$eDate['cccProduct'][$farm] = $cProductByFarm->reindex(['product', 'category']);

						}

					}
					break;

			}

		} else {
			$index = 'category';
			$eDate['ccProduct'] = $cProduct->reindex(['product', 'category']);
		}

		$eDate['productsIndex'] = $index;
		$eDate['productsEmpty'] = $cProduct->empty();

	}

	public static function applyGrid(Product $eProduct, \selling\Grid $eGrid): void {

		if($eGrid->notEmpty()) {
			$eProduct['price'] = $eGrid['price'];
			$eProduct['priceInitial'] = $eGrid['priceInitial'];
		}

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

			$eProduct->buildIndex(['available', 'price', 'priceDiscount'], $input, $index);

			$cProduct->append($eProduct);

		}

		if($cProduct->empty()) {
			Product::fail('empty');
		}

		return $cProduct;

	}

	public static function create(Product $e): void {

		Product::model()->beginTransaction();

			if($e['parent']) {
				$e['parentCategory'] = $e['cRelation']->first()['child']['product']['category'];
			}

			Product::model()
				->option('add-ignore')
				->insert($e);

			if($e['catalog']->notEmpty()) {
				CatalogLib::recalculate($e['catalog']);
			}

			if($e['parent']) {
				RelationLib::createByParent($e, $e['cRelation']);
			}

		Product::model()->commit();

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

			if($eProduct['parent']) {
				RelationLib::deleteByParent($eProduct);
			}

			RelationLib::deleteByChild($eProduct);

			Product::model()->delete($eProduct);

			if($eProduct['catalog']->notEmpty()) {
				CatalogLib::recalculate($eProduct['catalog']);
			}

		Product::model()->commit();

	}

	public static function deleteCollection(\Collection $cProduct): void {

		foreach($cProduct as $eProduct) {
			self::delete($eProduct);
		}

	}

	public static function getProductByCatalogs(array $catalogs, \selling\Product $eProductSelling): Product {

		return Product::model()
			->select('id')
			->whereCatalog('IN', $catalogs)
			->whereProduct($eProductSelling)
			->get();

	}

	public static function associateItems(\selling\Sale $e, array $catalogs): void {

		$cItem = \selling\Item::model()
			->select('id', 'product')
			->whereSale($e)
			->whereIngredientOf(NULL)
			->whereProduct('!=', NULL)
			->getCollection();

		if($cItem->empty()) {
			return;
		}

		$cProductSelling = $cItem->getColumnCollection('product');

		$cProduct = Product::model()
			->select('id', 'product')
			->whereCatalog('IN', $catalogs)
			->whereProduct('IN', $cProductSelling)
			->getCollection(index: 'product');

		foreach($cItem as $eItem) {

			\selling\Item::model()->update($eItem, [
				'shopProduct' => $cProduct[$eItem['product']['id']] ?? new Product()
			]);

		}

	}

	public static function getReallyAvailable(Product $eProduct, \selling\Product $eProductSelling, \Collection $cItem): ?float {

		if($eProduct['parent']) {
			return NULL;
		}

		$eProductSelling->expects(['id']);

		if($cItem->notEmpty()) {
			$eItem = $cItem->find(fn($eItem) => $eProductSelling->is($eItem['product']), limit: 1);
			$number = $eItem->empty() ? 0 : $eItem['number'];
		} else {
			$number = 0;
		}

		if($eProduct['available'] !== NULL) {

			$number += $eProduct['available'];

			return round(
				($eProduct['limitMax'] === NULL) ?
					$number :
					min($eProduct['limitMax'], $number),
				2);

		}  else {

			return ($eProduct['limitMax'] === NULL) ?
				NULL :
				round($eProduct['limitMax'], 2);

		}

	}

	public static function addAvailable(\selling\Sale $eSale, \Collection $cItem): void {
		self::setAvailable($eSale, $cItem, '+');
	}

	public static function removeAvailable(\selling\Sale $eSale, \Collection $cItem): void {
		self::setAvailable($eSale, $cItem, '-');
	}

	private static function setAvailable(\selling\Sale $eSale, \Collection $cItem, string $sign): void {

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

	public static function update(Product $e, array $properties): void {

		if(array_delete($properties, 'priceDiscount')) {
			$properties[] = 'priceInitial';
		}

		$hasChildren = array_delete($properties, 'children');

		Product::model()->beginTransaction();

			parent::update($e, $properties);

			if($hasChildren) {

				RelationLib::deleteByParent($e);
				RelationLib::createByParent($e, $e['cRelation']);

			}

		Product::model()->commit();

	}

}
?>
