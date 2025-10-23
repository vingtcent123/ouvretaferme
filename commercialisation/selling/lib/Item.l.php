<?php
namespace selling;

class ItemLib extends ItemCrud {

	public static function getPropertiesUpdate(): \Closure {
		return function(Item $e) {

			$e->expects([
				'sale' => ['hasVat']
			]);

			$properties = ['name', 'description', 'quality', 'locked', 'packaging', 'number', 'unitPrice', 'unitPriceDiscount', 'price'];

			if($e['sale']['hasVat']) {
				$properties[] = 'vatRate';
			}

			return $properties;

		};
	}

	public static function getProductsBySales(\farm\Farm $eFarm, \Collection $cSale): \Collection {

		AnalyzeLib::filterItemComposition($eFarm);

		$ccItem = Item::model()
			->select([
				'sale',
				'product' => ProductElement::getSelection(),
				'customer' => ['type', 'name'],
				'packaging', 'number',
				'unit' => \selling\Unit::getSelection(),
				'containsComposition' => new \Sql('productComposition', 'bool'),
				'containsIngredient' => new \Sql('ingredientOf IS NOT NULL', 'bool')
			])
			->join(Product::model(), 'm2.id = m1.product')
			->where('sale', 'IN', $cSale)
			->sort('sale')
			->getCollection(NULL, NULL, ['product', NULL]);

		$ccItem->sort(function(\Collection $c1, \Collection $c2) {
			return \L::getCollator()->compare(
				$c1->first()['product']->getName(),
				$c2->first()['product']->getName()
			);
		});

		$ccItem->map(fn($cItem) => $cItem->sort(['customer' => ['name']]));

		return $ccItem;

	}

	public static function applyGrid(Item $eItem, Grid $eGrid): void {

		if($eGrid->notEmpty()) {
			$eItem['unitPrice'] = $eGrid['price'];
			$eItem['unitPriceInitial'] = $eGrid['priceInitial'];
		}

	}

	public static function getNew(Sale $eSale, Product $eProduct, Grid $eGrid): Item {

		$eSale->expects(['farm', 'customer']);

		$eFarm = $eSale['farm'];

		$eItem = new \selling\Item([
			'farm' => $eFarm,
			'sale' => $eSale,
			'product' => $eProduct,
			'vatRate' => SellingSetting::VAT_RATES[$eFarm->getSelling('defaultVat')],
			'quality' => $eProduct->empty() ? new \plant\Size() : $eProduct['quality'],
			'customer' => $eSale['customer'],
			'locked' => \selling\Item::PRICE,
		]);

		if($eProduct->notEmpty()) {

			$eItem['packaging'] = NULL;
			$eItem['unitPrice'] = NULL;
			$eItem['unit'] = $eProduct['unit'];

			self::applyGrid($eItem, $eGrid);

			if($eSale['type'] === Customer::PRO) {
				$eItem['packaging'] ??= $eProduct['proPackaging'];
			}

			$eItem['unitPrice'] ??= $eProduct[$eSale['type'].'Price'];
			$eItem['unitPrice'] ??= match($eSale['type']) {
				Customer::PRO => $eProduct->calcProMagicPrice($eSale['hasVat']),
				Customer::PRIVATE => $eProduct->calcPrivateMagicPrice($eSale['hasVat']),
			};
			$eItem['unitPriceInitial'] ??= $eProduct[$eSale['type'].'PriceInitial'];

		} else {

			$eItem['unit'] = new Unit();
			$eItem['unitPriceInitial'] = NULL;
			$eItem['packaging'] = NULL;

		}

		return $eItem;

	}

	public static function containsProductIngredient(Product $eProduct): bool {

		return Item::model()
			->whereProduct($eProduct)
			->whereIngredientOf('!=', NULL)
			->exists();

	}

	public static function containsProductsIngredient(\Collection $cProduct): bool {

		return Item::model()
			->whereProduct('IN', $cProduct)
			->whereIngredientOf('!=', NULL)
			->exists();

	}

	public static function getBySales(\farm\Farm $eFarm, \Collection $cSale): \Collection {

		AnalyzeLib::filterItemComposition($eFarm);

		$ccItem = Item::model()
			->select(Item::getSelection() + [
				'customer' => ['type', 'name']
			])
			->where('sale', 'IN', $cSale)
			->sort('sale')
			->getCollection(NULL, NULL, ['sale', NULL]);

		$ccItem->sort(function(\Collection $c1, \Collection $c2) {
			return \L::getCollator()->compare(
				$c1->first()['customer']->empty() ? '' : $c1->first()['customer']->getName(),
				$c2->first()['customer']->empty() ? '' : $c2->first()['customer']->getName()
			);
		});

		$ccItem->map(fn($cItem) => $cItem->sort(['product' => ['name']]));

		return $ccItem;

	}

	public static function getByProduct(Product $eProduct): \Collection {

		if($eProduct['composition'] === FALSE) {
			AnalyzeLib::filterItemComposition($eProduct['farm']);
		}

		return Item::model()
			->select([
				'sale' => ['farm', 'shop', 'shopShared', 'hasVat', 'taxes', 'discount', 'shippingVatRate', 'shippingVatFixed', 'document'],
				'customer' => ['type', 'name'],
				'quantity' => new \Sql('IF(packaging IS NULL, 1, packaging) * number', 'float'),
				'unit' => \selling\Unit::getSelection(),
				'unitPrice',
				'price',
				'deliveredAt'
			])
			->join(Sale::model(), 'm2.id = m1.sale')
			->whereProduct($eProduct)
			->where('m1.stats', TRUE)
			->sort(['m1.deliveredAt' => SORT_DESC])
			->getCollection(0, 50);

	}

	public static function getSummaryByDate(\farm\Farm $eFarm, \shop\Date $eDate): \Collection {

		return Item::model()
			->select([
				'name', 'quality',
				'unit' => \selling\Unit::getSelection(),
				'price' => new \Sql('SUM(price)', 'float'),
				'number' => new \Sql('SUM(number)', 'float'),
				'packaging',
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'product' => ['vignette', 'farm', 'composition'],
				'productComposition',
				'deliveredAt' => fn() => $eDate['deliveryDate'],
				'cItemIngredient' => SaleLib::delegateIngredients($eDate['deliveryDate'], 'product')
			])
			->whereFarm($eFarm)
			->whereStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED, Sale::CLOSED])
			->whereIngredientOf(NULL)
			->whereShopDate($eDate)
			->group(['product', 'productComposition', 'name', 'unit', 'packaging', 'quality'])
			->sort('name')
			->getCollection();

	}

	public static function getSummaryBySales(\Collection $cSale): \Collection {

		return Item::model()
			->select([
				'name', 'quality',
				'unit' => \selling\Unit::getSelection(),
				'price' => new \Sql('SUM(price)', 'float'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'product' => ProductElement::getSelection(),
				'productComposition' => fn() => FALSE
			])
			->whereIngredientOf(NULL)
			->whereSale('IN', $cSale)
			->group(['product', 'name', 'unit', 'quality'])
			->sort('name')
			->getCollection();
	}

	public static function getForPastStock(\farm\Farm $eFarm): \Collection {

		return Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, number, number * packaging))', 'float'),
				'product',
				'deliveredAt'
			])
			->whereFarm($eFarm)
			->whereNumber('!=', NULL)
			->whereStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED, Sale::CLOSED])
			->whereStats(TRUE)
			->whereDeliveredAt('IN', [currentDate(), date('Y-m-d', strtotime('yesterday'))])
			->group(['deliveredAt', 'product'])
			->getCollection(index: ['product', 'deliveredAt']);

	}

	public static function getForFutureStock(\farm\Farm $eFarm): \Collection {

		return Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, number, number * packaging))', 'float'),
				'product'
			])
			->whereFarm($eFarm)
			->whereStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED, Sale::CLOSED])
			->whereStats(TRUE)
			->whereDeliveredAt('>', currentDate())
			->group('product')
			->getCollection(index: 'product');

	}

	public static function checkNewItems(Sale $eSale, array $post): \Collection {

		array_expects($post, ['locked']);

		$cItem = ItemLib::getByIds(array_keys($post['locked']), index: 'id');

		$cItemNew = new \Collection();

		foreach($post['locked'] as $key => $locked) {

			if($cItem->offsetExists($key) === FALSE) {
				continue;
			}

			$eItem = $cItem[$key];
			$type = ($post['type'][$key] ?? NULL);

			// Vérifications de sécurité
			if($type === 'parent') {

				if(
					$eSale->isMarketSale() === FALSE or
					$eItem['sale']['id'] !== $eSale['marketParent']['id']
				) {
					continue;
				}

			} else if($type === 'standalone') {

				if($eItem['sale']['id'] !== $eSale['id']) {
					continue;
				}

			} else {
				continue;
			}

			$eItemNew = new Item([
				'id' => ($type === 'parent') ? NULL : $eItem['id'],
				'parent' => ($type === 'parent') ? $eItem : $eItem['parent'],
				'sale' => $eSale,
				'farm' => $eItem['farm'],
				'customer' => $eSale['customer'],
				'product' => $eItem['product'],
				'name' => $eItem['name'],
				'quality' => $eItem['quality'],
				'unit' => $eItem['unit'],
				'unitPriceInitial' => $eItem['unitPriceInitial'],
				'vatRate' => $eItem['vatRate'],
			]);

			$eItemNew->buildIndex(['locked', 'number', 'unitPrice', 'unitPriceDiscount', 'price', 'packaging'], $post, $key);

			$cItemNew[] = $eItemNew;

		}

		return $cItemNew;

	}

	public static function createCollection(Sale $eSale, \Collection $c, bool $replace = FALSE): void {

		if($c->empty()) {
			throw new \Exception('Collection must not be empty');
		}

		$c->map(fn($e) => self::prepareCreate($e));

		Item::model()->beginTransaction();

			if($replace) {

				$cOld = Item::model()
					->select(Item::getSelection())
					->whereSale($eSale)
					->getCollection();

				self::deleteCollection($eSale, $cOld);

			}

			foreach($c as $e) {

				Item::model()->insert($e);

				if($e['productComposition']) {
					self::createIngredients($e);
				}

			}

			SaleLib::recalculate($eSale);

			\shop\ProductLib::removeAvailable($eSale, $c);

		Item::model()->commit();

	}

	public static function create(Item $e): void {

		self::prepareCreate($e);

		Item::model()->beginTransaction();

		Item::model()->insert($e);

		if($e['productComposition']) {
			self::createIngredients($e);
		}

		SaleLib::recalculate($e['sale']);

		Item::model()->commit();

	}

	public static function updateSaleCollection(Sale $eSale, \Collection $cItem): void {

		if($cItem->empty()) {
			return;
		}

		// La même vente pour tout le monde
		$cItem->validateProperty('sale', $eSale);

		Item::model()->beginTransaction();

			Item::model()
				->whereSale($eSale)
				->or(
					fn() => $this->whereId('IN', $cItem->find(fn($eItem) => $eItem['id'] !== NULL)),
					fn() => $this->whereIngredientOf('IN', $cItem->find(fn($eItem) => $eItem['id'] !== NULL)),
					fn() => $this->whereParent('IN', $cItem->find(fn($eItem) => $eItem['parent']->notEmpty())->getColumnCollection('parent'))
				)
				->delete();

			if($eSale->isMarketSale()) {

				// On n'enregistre pas les ventes à 0.0 sur le logiciel de caisse
				$cItemFiltered = $cItem->find(fn($eItem) => (
					($eItem['locked'] === Item::UNIT_PRICE and $eItem['number'] !== NULL and $eItem['price'] !== NULL) or
					($eItem['locked'] === Item::PRICE and $eItem['number'] !== NULL and $eItem['unitPrice'] !== NULL) or
					($eItem['locked'] === Item::NUMBER and $eItem['unitPrice'] !== NULL and $eItem['price'] !== NULL)
				));

				if($cItemFiltered->notEmpty()) {
					ItemLib::createCollection($eSale, $cItemFiltered);
				} else {
					SaleLib::recalculate($eSale);
				}

			} else {
				ItemLib::createCollection($eSale, $cItem);
			}

		Item::model()->commit();

	}

	public static function createIngredients(Item $e): void {

		$e->expects(['productComposition', 'deliveredAt']);

		if($e['productComposition'] === FALSE) {
			throw new \Exception('Invalid call');
		}

		Item::model()
			->select([
				'cItemIngredient' => SaleLib::delegateIngredients($e['deliveredAt'], 'product')
			])
			->get($e);

		if($e['cItemIngredient']->empty()) {
			return;
		}

		$cItemIngredient = new \Collection();
		self::buildIngredients($cItemIngredient, $e, $e['cItemIngredient']);
		Item::model()->insert($cItemIngredient);

	}

	public static function buildIngredients(\Collection $cItemIngredient, Item $eItemComposition, \Collection $cItemCopy): \Collection {

		$ingredientsPrice = $cItemCopy->sum('price');

		$ratio = ($ingredientsPrice > 0) ? $eItemComposition['price'] / $ingredientsPrice : NULL;

		foreach($cItemCopy as $eItemCopy) {

			$copyPrice = ($ratio !== NULL) ? $eItemCopy['price'] * $ratio : $eItemComposition['price'] / $cItemCopy->count();
			$copyPriceStats = ($ratio !== NULL) ? $eItemCopy['priceStats'] * $ratio : $eItemComposition['priceStats'] / $cItemCopy->count();
			$copyPackaging = $eItemCopy['packaging'];
			$copyNumber = $eItemCopy['number'] * $eItemComposition['number'] * ($eItemComposition['packaging'] ?? 1);

			$eItemIngredient = (clone $eItemComposition);
			$eItemIngredient->merge([
			  'id' => NULL,
			  'name' => $eItemCopy['name'],
			  'product' => $eItemCopy['product'],
			  'productComposition' => FALSE,
			  'ingredientOf' => $eItemComposition,
			  'quality' => $eItemCopy['quality'],
			  'parent' => new Item(),
			  'packaging' => $eItemCopy['packaging'],
			  'unit' => $eItemCopy['unit'],
			  'unitPrice' => ($copyNumber > 0 and $copyPackaging > 0) ? $copyPrice / $copyNumber / $copyPackaging : $eItemCopy['unitPrice'],
			  'number' => $copyNumber,
			  'price' => $copyPrice,
			  'priceStats' => $copyPriceStats,
			  'vatRate' => $eItemCopy['vatRate'],
			  'stats' => $eItemComposition['stats']
			]);

			$cItemIngredient[] = $eItemIngredient;


		}

		return $cItemIngredient;

	}

	public static function update(Item $e, array $properties): void {

		if($e->canUpdate() === FALSE) {
			Item::fail('canNotUpdate');
		}

		if(in_array('name', $properties)) {
			self::checkMarketDuplicate($e['sale'], new \Collection([$e]));
		}

		if(array_intersect(['unitPrice', 'number', 'packaging', 'vatRate', 'price'], $properties)) {
			self::preparePricing($e, $properties);
		}

		if(array_delete($properties, 'unitPriceDiscount')) {
			$properties[] = 'unitPriceInitial';
		}

		Item::model()->beginTransaction();

		parent::update($e, $properties);

		if(in_array('price', $properties)) {
			SaleLib::recalculate($e['sale']);
		}

		if($e['productComposition']) {
			self::updateIngredients($e);
		}

		Item::model()->commit();

	}

	public static function updateIngredients(Item $e): void {

		self::deleteIngredients($e);
		self::createIngredients($e);

	}

	public static function delete(Item $e): void {

		/* Cette méthode ne remet pas à jour le disponible sur les boutiques */

		$e->expects(['sale']);

		Item::model()->beginTransaction();

			parent::delete($e);

			if($e['productComposition']) {
				self::deleteIngredients($e);
			}

			SaleLib::recalculate($e['sale']);

		Item::model()->commit();

	}

	public static function deleteCollection(Sale $eSale, \Collection $c): void {

		$c->expects(['sale']);

		Item::model()->beginTransaction();

			foreach($c as $e) {

				parent::delete($e);

				if($e['productComposition']) {
					self::deleteIngredients($e);
				}

			}

			SaleLib::recalculate($eSale);

			\shop\ProductLib::addAvailable($eSale, $c);

		Item::model()->commit();

	}

	public static function deleteIngredients(Item $e): void {

		if($e['productComposition'] === FALSE) {
			throw new \Exception('Invalid call');
		}

		Item::model()
			->whereIngredientOf($e)
			->delete();

	}

	public static function prepareCreate(Item $e): void {

		$e->expects([
			'sale' => [
				'deliveredAt', 'preparationStatus', 'shop', 'shopDate', 'type', 'stats', 'hasVat', 'discount'
			],
		]);

		if($e['product']->notEmpty()) {
			$e['product']->expects(['composition']);
		}
		
		$eSale = $e['sale'];

		$e['deliveredAt'] = $eSale['deliveredAt'];
		$e['shop'] = $eSale['shop'];
		$e['shopDate'] = $eSale['shopDate'];
		$e['shopProduct'] ??= new \shop\Product();
		$e['type'] = $eSale['type'];
		$e['discount'] = $eSale['discount'];
		$e['stats'] = $eSale['stats'];
		$e['status'] = $eSale['preparationStatus'];
		$e['productComposition'] = $e['product']->notEmpty() ? $e['product']['composition'] : FALSE;

		if($eSale['hasVat'] === FALSE) {
			$e['vatRate'] = 0.0;
		}

		self::preparePricing($e);

	}

	private static function preparePricing(Item $e, array &$properties = []): void {

		$e->expects([
			'sale' => ['farm', 'taxes', 'origin'],
			'locked',
			'unitPrice', 'number', 'packaging', 'vatRate', 'discount'
		]);

		if($e['sale']->isMarket()) {

			// Marché en cours, à priori zéro vente à la création
			if($e['sale']['preparationStatus'] === Sale::SELLING) {
				$e['price'] = 0.0;
				$e['priceStats'] = 0.0;
				$e['number'] = 0.0;
			} else {
				$e['price'] = NULL;
				$e['priceStats'] = NULL;
			}

			$properties[] = 'price';
			$properties[] = 'priceStats';

		} else {

			switch($e['locked']) {

				case Item::PRICE :

					$price = $e['unitPrice'] * $e['number'];

					if($e['packaging']) {
						$price *= $e['packaging'];
					}

					$e['price'] = round($price, 2);

					$properties[] = 'price';

					break;

				case Item::NUMBER :

					if($e['unitPrice'] === 0.0) {
						throw new \FailException('Unit price must not be null');
					}

					$number = $e['price'] / $e['unitPrice'];

					if($e['packaging']) {
						$number /= $e['packaging'];
					}

					$e['number'] = round($number, 2);

					$properties[] = 'number';

					break;

				case Item::UNIT_PRICE :

					if($e['number'] === 0.0) {
						throw new \FailException('Number must not be null');
					}

					$unitPrice = $e['price'] / $e['number'];

					if($e['packaging']) {
						$unitPrice /= $e['packaging'];
					}

					$e['unitPrice'] = round($unitPrice, 2);

					$properties[] = 'unitPrice';

					break;

			}

			self::preparePriceStats($e);

		}

		$properties[] = 'priceStats';

	}

	public static function preparePriceStats(Item $e): void {

		$priceStats = match($e['sale']['taxes']) {
			Sale::INCLUDING => $e['price'] / (1 + $e['vatRate'] / 100),
			Sale::EXCLUDING => $e['price'],
			NULL => $e['price']
		};

		if($e['discount'] > 0) {
			$priceStats *= (100 - $e['discount']) / 100;
		}

		$e['priceStats'] = round($priceStats, 2);

	}

	public static function isCompatible(Sale $eSale, Product $eProduct): bool {

		$eProduct->expects(['farm']);
		$eSale->expects(['farm']);

		return $eProduct['farm']['id'] === $eSale['farm']['id'];

	}

	public static function build(Sale $eSale, array $input, bool $errorIfEmpty): \Collection {

		$eSale->expects(['customer', 'farm']);

		$count = count((array)($input['product'] ?? []));

		if($count === 0) {

			if($errorIfEmpty) {
				Item::fail('createEmpty');
			}

			return new \Collection();
		}

		$cItem = new \Collection();

		$fw = new \FailWatch();

		$positions = array_keys($input['product'] ?? []);

		foreach($positions as $position) {

			$eItem = new Item([
				'sale' => $eSale,
				'farm' => $eSale['farm'],
				'customer' => $eSale['customer'],
				'discount' => $eSale['discount']
			]);

			$eItem->buildIndex(['product', 'quality', 'name', 'packaging', 'locked', 'unit', 'unitPrice', 'unitPriceDiscount', 'number', 'price', 'vatRate'], $input, $position, new \Properties('create'));

			$cItem[] = $eItem;

		}

		if($fw->ok()) {
			self::checkMarketDuplicate($eSale, $cItem);
		}

		return $cItem;

	}

	protected static function checkMarketDuplicate(Sale $eSale, \Collection $cItem) {

		$eSale->expects(['origin']);

		if($eSale->isMarket() === FALSE) {
			return;
		}

		$addNames = [];
		$addIds = [];

		foreach($cItem as $eItem) {
			if($eItem['product']->empty()) {
				$addNames[] = toFqn($eItem['name']);
			}
			if(isset($eItem['id'])) {
				$addIds[] = $eItem['id'];
			}
		}

		$countNames = array_count_values($addNames);

		foreach($cItem as $eItem) {

			$count = 0;

			if($eItem['product']->empty()) {
				$count += $countNames[toFqn($eItem['name'])];
			}

			$count += Item::model()
				->whereSale($eSale)
				->whereName($eItem['name'])
				->whereProduct(NULL)
				->count();

			if($count > 1) {
				Item::fail('createDuplicateNameMarket', ['name' => $eItem['name']]);
			}

		}

		$addProducts = [];
		foreach($cItem as $eItem) {
			if($eItem['product']->notEmpty()) {
				$addProducts[] = $eItem['product']['id'];
			}
		}

		$countProducts = array_count_values($addProducts);

		foreach($cItem as $eItem) {

			$count = 0;

			if($eItem['product']->notEmpty()) {
				$count += $countProducts[$eItem['product']['id']];
			}

			if($addIds) {
				Item::model()->whereId('NOT IN', $addIds);
			}

			$count += Item::model()
				->whereSale($eSale)
				->whereProduct($eItem['product'])
				->count();

			if($count > 1) {

				Product::model()
					->select('name')
					->get($eItem['product']);

				Item::fail('createDuplicateProductMarket', ['name' => encode($eItem['product']['name'])]  );

			}

		}

	}

}
?>
