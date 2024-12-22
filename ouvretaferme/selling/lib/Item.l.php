<?php
namespace selling;

class ItemLib extends ItemCrud {

	public static function getPropertiesUpdate(): \Closure {
		return function(Item $e) {

			$e->expects([
				'sale' => ['hasVat']
			]);

			$properties = ['name', 'quality', 'description', 'locked', 'packaging', 'number', 'unitPrice', 'price'];

			if($e['sale']['hasVat']) {
				$properties[] = 'vatRate';
			}

			return $properties;

		};
	}

	public static function getProductsBySales(\Collection $cSale): \Collection {

		$ccItem = Item::model()
			->select([
				'sale',
				'product' => ['name', 'variety', 'vignette', 'size'],
				'customer' => ['type', 'name'],
				'packaging', 'number',
				'unit'
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

	public static function getBySale(Sale $eSale): \Collection {

		return Item::model()
			->select(Item::getSelection())
			->whereSale($eSale)
			->getCollection();

	}

	public static function getBySales(\Collection $cSale): \Collection {

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

		return Item::model()
			->select([
				'sale' => ['farm', 'hasVat', 'taxes', 'shippingVatRate', 'shippingVatFixed', 'document'],
				'customer' => ['type', 'name'],
				'quantity' => new \Sql('IF(packaging IS NULL, 1, packaging) * number', 'float'),
				'unit' => ['fqn', 'by', 'singular', 'plural', 'short', 'type'],
				'unitPrice',
				'price',
				'deliveredAt'
			])
			->join(Sale::model(), 'm2.id = m1.sale')
			->whereProduct($eProduct)
			->where('m2.marketParent', NULL)
			->sort(['m1.deliveredAt' => SORT_DESC])
			->getCollection(0, 50);

	}

	public static function getSummaryByDate(\shop\Date $eDate): \Collection {

		return Item::model()
			->select([
				'name', 'quality',
				'unit' => ['fqn', 'by', 'singular', 'plural', 'short', 'type'],
				'price' => new \Sql('SUM(price)', 'float'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'product' => ['vignette']
			])
			->whereFarm($eDate['farm'])
			->whereStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED])
			->whereShopDate($eDate)
			->group(['product', 'name', 'unit', 'quality'])
			->sort('name')
			->getCollection();
	}

	public static function getSummaryBySales(\Collection $cSale): \Collection {

		return Item::model()
			->select([
				'name', 'quality',
				'unit' => ['fqn', 'by', 'singular', 'plural', 'short', 'type'],
				'price' => new \Sql('SUM(price)', 'float'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'product' => ['vignette']
			])
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
			->whereStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED])
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
			->whereStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED])
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
					$eSale['marketParent']->empty() or
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
				'farm' => $eSale['farm'],
				'customer' => $eSale['customer'],
				'product' => $eItem['product'],
				'name' => $eItem['name'],
				'quality' => $eItem['quality'],
				'unit' => $eItem['unit'],
				'vatRate' => $eItem['vatRate'],
			]);

			$eItemNew->buildIndex(['locked', 'number', 'unitPrice', 'price', 'packaging'], $post, $key);

			$cItemNew[] = $eItemNew;

		}

		return $cItemNew;

	}

	public static function createCollection(\Collection $c): void {

		if($c->empty()) {
			throw new \Exception('Collection must not be empty');
		}

		$c->map(fn($e) => self::prepareCreate($e));

		Item::model()->beginTransaction();

			Item::model()->insert($c);

			SaleLib::recalculate($c->first()['sale']);

			\shop\ProductLib::removeAvailable($c);

		Item::model()->commit();

	}

	public static function create(Item $e): void {

		self::prepareCreate($e);

		Item::model()->beginTransaction();

		Item::model()->insert($e);

		SaleLib::recalculate($e['sale']);

		Item::model()->commit();

	}

	public static function updateSaleCollection(Sale $eSale, \Collection $cItem): void {

		if($cItem->empty()) {
			return;
		}

		// La même vente pour tout le monde
		$eSale->expects(['marketParent']);
		$cItem->validateProperty('sale', $eSale);

		Item::model()->beginTransaction();

			Item::model()
				->whereSale($eSale)
				->or(
					fn() => $this->whereId('IN', $cItem->find(fn($eItem) => $eItem['id'] !== NULL)),
					fn() => $this->whereParent('IN', $cItem->find(fn($eItem) => $eItem['parent']->notEmpty())->getColumnCollection('parent'))
				)
				->delete();

			if($eSale['marketParent']->notEmpty()) {

				// On n'enregistre pas les ventes à 0.0 sur le logiciel de caisse
				$cItemFiltered = $cItem->find(fn($eItem) => (
					($eItem['locked'] === Item::UNIT_PRICE and $eItem['number'] !== NULL and $eItem['price'] !== NULL) or
					($eItem['locked'] === Item::PRICE and $eItem['number'] !== NULL and $eItem['unitPrice'] !== NULL) or
					($eItem['locked'] === Item::NUMBER and $eItem['unitPrice'] !== NULL and $eItem['price'] !== NULL)
				));

				if($cItemFiltered->notEmpty()) {
					ItemLib::createCollection($cItemFiltered);
				} else {
					SaleLib::recalculate($eSale);
				}

			} else {
				ItemLib::createCollection($cItem);
			}

		Item::model()->commit();

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

		Item::model()->beginTransaction();

		parent::update($e, $properties);

		if(in_array('price', $properties)) {
			SaleLib::recalculate($e['sale']);
		}

		Item::model()->commit();

	}

	public static function delete(Item $e): void {

		/* Cette méthode ne remet pas à jour le disponible sur les boutiques */

		$e->expects(['sale']);

		Item::model()->beginTransaction();

			parent::delete($e);

			SaleLib::recalculate($e['sale']);

		Item::model()->commit();

	}

	public static function deleteCollection(\Collection $c): void {

		$c->expects(['sale']);

		Item::model()->beginTransaction();

			$eSale = $c->first()['sale'];

			foreach($c as $e) {
				parent::delete($e);
			}

			SaleLib::recalculate($eSale);

			\shop\ProductLib::addAvailable($c);

		Item::model()->commit();

	}

	public static function deleteBySale(Sale $e): void {

		Item::model()->beginTransaction();

		Item::model()
			->whereSale($e)
			->delete();

		SaleLib::recalculate($e);

		Item::model()->commit();

	}

	public static function prepareCreate(Item $e): void {

		$e->expects([
			'sale' => ['deliveredAt', 'preparationStatus', 'shop', 'shopDate', 'type', 'stats', 'hasVat'],
		]);

		$e['deliveredAt'] = $e['sale']['deliveredAt'];
		$e['shop'] = $e['sale']['shop'];
		$e['shopDate'] = $e['sale']['shopDate'];
		$e['shopProduct'] ??= new \shop\Product();
		$e['type'] = $e['sale']['type'];
		$e['stats'] = $e['sale']['stats'];
		$e['status'] = $e['sale']['preparationStatus'];

		if($e['sale']['hasVat'] === FALSE) {
			$e['vatRate'] = 0.0;
		}

		self::preparePricing($e);

	}

	private static function preparePricing(Item $e, array &$properties = []): void {

		$e->expects([
			'sale' => ['farm', 'taxes', 'market'],
			'locked',
			'unitPrice', 'number', 'packaging', 'vatRate'
		]);

		if($e['sale']['market']) {

			// Marché en cours, à priori zéro vente à la création
			if($e['sale']['preparationStatus'] === Sale::SELLING) {
				$e['price'] = 0.0;
				$e['priceExcludingVat'] = 0.0;
				$e['number'] = 0.0;
			} else {
				$e['price'] = NULL;
				$e['priceExcludingVat'] = NULL;
			}

			$properties[] = 'price';
			$properties[] = 'priceExcludingVat';

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

			$e['priceExcludingVat'] = match($e['sale']['taxes']) {
				Sale::INCLUDING => round($e['price'] / (1 + $e['vatRate'] / 100), 2),
				Sale::EXCLUDING => $e['price'],
				NULL => $e['price']
			};

		}

		$properties[] = 'priceExcludingVat';

	}

	public static function isCompatible(Sale $eSale, Product $eProduct): bool {

		$eProduct->expects(['farm']);
		$eSale->expects(['farm']);

		return $eProduct['farm']['id'] === $eSale['farm']['id'];

	}

	public static function build(Sale $eSale, array $input): \Collection {

		$eSale->expects(['id', 'customer', 'farm', 'marketParent']);

		$count = count((array)($input['product'] ?? []));

		if($count === 0) {
			Item::fail('createEmpty');
			return new \Collection();
		}

		$cItem = new \Collection();

		$fw = new \FailWatch();

		for($position = 0; $position < $count; $position++) {

			$eItem = new Item([
				'sale' => $eSale,
				'farm' => $eSale['farm'],
				'customer' => $eSale['customer']
			]);

			$eItem->buildIndex(['product', 'quality', 'name', 'packaging', 'locked', 'discount', 'unit', 'unitPrice', 'number', 'price', 'vatRate'], $input, $position);

			$cItem[] = $eItem;

		}

		if($fw->ok()) {
			self::checkMarketDuplicate($eSale, $cItem);
		}

		return $cItem;

	}

	protected static function checkMarketDuplicate(Sale $eSale, \Collection $cItem) {

		$eSale->expects(['market']);

		if($eSale['market'] === FALSE) {
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
