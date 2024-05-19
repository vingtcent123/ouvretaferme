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

	public static function getBySalesForPlanning(\Collection $cSale): \Collection {

		$ccItem = Item::model()
			->select([
				'deliveredAt' => new \Sql('m1.deliveredAt'),
				'product' => ['name', 'variety', 'vignette', 'size'],
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, number, number * packaging))', 'float'),
				'unit'
			])
			->join(Product::model(), 'm2.id = m1.product')
			->where('m1.sale', 'IN', $cSale)
			->group(new \Sql('m1.deliveredAt, m1.product, m1.unit'))
			->sort(new \Sql('deliveredAt DESC'))
			->getCollection(NULL, NULL, ['deliveredAt', NULL]);

		$ccItem->map(fn($cItem) => $cItem->sort(['product' => ['name']]));

		return $ccItem;

	}

	public static function getProductsBySales(\Collection $cSale): \Collection {

		$ccItem = Item::model()
			->select([
				'sale',
				'product' => ['name', 'variety', 'vignette', 'size'],
				'customer' => ['name'],
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
				'customer' => ['name']
			])
			->where('sale', 'IN', $cSale)
			->sort('sale')
			->getCollection(NULL, NULL, ['sale', NULL]);

		$ccItem->sort(function(\Collection $c1, \Collection $c2) {
			return \L::getCollator()->compare(
				$c1->first()['customer']->empty() ? '' : $c1->first()['customer']['name'],
				$c2->first()['customer']->empty() ? '' : $c2->first()['customer']['name']
			);
		});

		$ccItem->map(fn($cItem) => $cItem->sort(['product' => ['name']]));

		return $ccItem;

	}

	public static function getByProduct(Product $eProduct): \Collection {

		return Item::model()
			->select([
				'sale' => ['farm', 'hasVat', 'taxes', 'shippingVatRate', 'shippingVatFixed', 'document'],
				'customer' => ['name'],
				'quantity' => new \Sql('IF(packaging IS NULL, 1, packaging) * number', 'float'),
				'unit', 'unitPrice',
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
				'unit',
				'price' => new \Sql('SUM(price)', 'float'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'product' => ['vignette']
			])
			->whereFarm($eDate['farm'])
			->whereShopDate($eDate)
			->group(['product', 'name', 'unit', 'quality'])
			->sort('name')
			->getCollection();
	}

	public static function getSummaryBySales(\Collection $cSale): \Collection {

		return Item::model()
			->select([
				'name', 'quality',
				'unit',
				'price' => new \Sql('SUM(price)', 'float'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'product' => ['vignette']
			])
			->whereSale('IN', $cSale)
			->group(['product', 'name', 'unit', 'quality'])
			->sort('name')
			->getCollection();
	}

	public static function createCollection(\Collection $c): void {

		if($c->empty()) {
			throw new \Exception('Collection must not be empty');
		}

		$c->map(fn($e) => self::prepareCreate($e));

		Item::model()->beginTransaction();

		Item::model()->insert($c);

		SaleLib::recalculate($c->first()['sale']);

		Item::model()->commit();

	}

	public static function create(Item $e): void {

		self::prepareCreate($e);

		Item::model()->beginTransaction();

		Item::model()->insert($e);

		SaleLib::recalculate($e['sale']);

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

		$e->expects(['sale']);

		Item::model()->beginTransaction();

		parent::delete($e);

		SaleLib::recalculate($e['sale']);

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

					$number = $e['price'] / $e['unitPrice'];

					if($e['packaging']) {
						$number /= $e['packaging'];
					}

					$e['number'] = round($number, 2);

					$properties[] = 'number';

					break;

				case Item::UNIT_PRICE :

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
