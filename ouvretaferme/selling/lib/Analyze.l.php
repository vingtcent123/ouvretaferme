<?php
namespace selling;

class AnalyzeLib {

	public static function getFarmMonths(\farm\Farm $eFarm, int $year): \Collection {

		Item::model()->whereFarm($eFarm);

		return self::getMonths($year);

	}

	public static function getCustomerMonths(Customer $eCustomer, int $year): \Collection {

		Item::model()->whereCustomer($eCustomer);

		return self::getMonths($year);

	}

	public static function getProductMonths(Product $eProduct, int $year, \Search $search = new \Search()): \Collection {

		Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->whereProduct($eProduct)
			->where('number != 0');

		return self::getMonths($year, $search);

	}

	public static function getProductsMonths(\Collection $cProduct, int $year, \Search $search = new \Search()): \Collection {

		if($search->get('type')) {
			Item::model()->where('m2.type', $search->get('type'));
		}

		self::filterItemStats(TRUE);

		$ccItem = Item::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM deliveredAt)', 'int'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'unit',
				'average' => new \Sql('SUM(priceExcludingVat) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float')
			])
			->join(Customer::model(), 'm1.customer = m2.id')
			->where('number != 0')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->whereProduct('IN', $cProduct)
			->group(new \Sql('m1_month, unit'))
			->sort(new \Sql('m1_month DESC'))
			->getCollection(NULL, NULL, ['month', NULL]);

		$cItemMonth = new \Collection();

		foreach($ccItem as $cItem) {

			$eItem = $cItem->first();
			$eItem['turnover'] = $cItem->sum('turnover');
			$eItem['cItem'] = $cItem;

			$cItemMonth[$eItem['month']] = $eItem;

		}

		return $cItemMonth;
	}

	private static function getMonths(int $year, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->whereType($type));

		self::filterItemStats();

		return Item::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM deliveredAt)', 'int'),
				'products' => new \Sql('COUNT(DISTINCT product)'),
				'customers' => new \Sql('COUNT(DISTINCT customer)'),
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'turnoverPrivate' => new \Sql('SUM(IF(type="'.Customer::PRIVATE.'", priceExcludingVat, 0))', 'float'),
				'turnoverPro' => new \Sql('SUM(IF(type="'.Customer::PRO.'", priceExcludingVat, 0))', 'float')
			])
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->group(new \Sql('month'))
			->sort(new \Sql('month DESC'))
			->getCollection(NULL, NULL, ['month']);

	}

	public static function getFarmWeeks(\farm\Farm $eFarm, int $year): \Collection {

		Item::model()->whereFarm($eFarm);

		return self::getWeeks($year);

	}

	public static function getCustomerWeeks(Customer $eCustomer, int $year): \Collection {

		Item::model()->whereCustomer($eCustomer);

		return self::getWeeks($year);

	}

	public static function getProductWeeks(Product $eProduct, int $year, \Search $search = new \Search()): \Collection {

		Item::model()
			->whereProduct($eProduct)
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->where('number != 0');

		return self::getWeeks($year, $search);

	}

	public static function getProductsWeeks(\Collection $cProduct, int $year, \Search $search = new \Search()): \Collection {

		Item::model()
			->whereProduct('IN', $cProduct)
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->where('number != 0');

		return self::getWeeks($year, $search);

	}

	private static function getWeeks(int $year, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->whereType($type));

		self::filterItemStats();

		return Item::model()
			->select([
				'week' => new \Sql('EXTRACT(week FROM deliveredAt)', 'int'),
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float')
			])
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->group(new \Sql('week'))
			->sort(new \Sql('week DESC'))
			->getCollection(NULL, NULL, ['week']);

	}

	public static function getFarmCustomers(\farm\Farm $eFarm, int $year, ?int $month, ?string $week, \Search $search): \Collection {

		Item::model()->where('m1.farm', $eFarm);

		return self::getCustomers($year, $month, $week, $search);

	}

	public static function getShopCustomers(\shop\Shop $eShop, int $year, ?int $month, ?string $week): \Collection {

		Item::model()->whereShop($eShop);

		return self::getCustomers($year, $month, $week);

	}

	public static function getProductCustomers(Product $eProduct, int $year, \Search $search = new \Search()): \Collection {
		return self::getProductsCustomers(new \Collection([$eProduct]), $year, $search);
	}

	public static function getProductsCustomers(\Collection $cProduct, int $year, \Search $search = new \Search()): \Collection {

		Item::model()->whereProduct('IN', $cProduct);

		Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->where('number != 0');

		return self::getCustomers($year, NULL, NULL, $search);

	}

	private static function getCustomers(int $year, ?int $month, ?string $week, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->where('m2.type', $type));

		self::filterItemStats(TRUE);

		return Item::model()
			->select([
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
				'customer' => ['name'],
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float')
			])
			->join(Customer::model(), 'm1.customer = m2.id')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'IN', [$year, $year - 1])
			->where($month ? 'EXTRACT(MONTH FROM deliveredAt) = '.$month : NULL)
			->where($week ? 'WEEK(deliveredAt, 1) = '.week_number($week) : NULL)
			->group(['m1_year', 'customer'])
			->sort(new \Sql('m1_turnover DESC'))
			->getCollection(NULL, NULL, ['year', 'customer']);

	}

	public static function getProductTypes(Product $eProduct, int $year, \Search $search = new \Search()): \Collection {
		return self::getProductsTypes(new \Collection([$eProduct]), $year, $search);
	}

	public static function getProductsTypes(\Collection $cProduct, int $year, \Search $search = new \Search()): \Collection {

		Item::model()->whereProduct('IN', $cProduct);

		Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->where('number != 0');

		return self::getTypes($year, NULL, NULL, $search);

	}

	private static function getTypes(int $year, ?int $month, ?string $week, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->where('m2.type', $type));

		self::filterItemStats(TRUE);

		return Item::model()
			->select([
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
				'type',
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float')
			])
			->join(Customer::model(), 'm1.customer = m2.id')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'IN', [$year, $year - 1])
			->where($month ? 'EXTRACT(MONTH FROM deliveredAt) = '.$month : NULL)
			->where($week ? 'WEEK(deliveredAt, 1) = '.week_number($week) : NULL)
			->group(['m1_year', 'm1_type'])
			->sort(new \Sql('m1_type'))
			->getCollection(NULL, NULL, ['year', 'type']);

	}

	public static function getMonthlyFarmCustomers(\farm\Farm $eFarm, int $year, \Search $search): \Collection {

		Item::model()->where('m1.farm', $eFarm);

		return self::getMonthlyCustomers($year, $search);

	}

	private static function getMonthlyCustomers(int $year, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->where('m2.type', $type));

		self::filterItemStats(TRUE);

		return Item::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM deliveredAt)', 'int'),
				'customer',
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float')
			])
			->join(Customer::model(), 'm1.customer = m2.id')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->group(['customer', 'm1_month'])
			->sort(new \Sql('m1_turnover DESC'))
			->getCollection(NULL, NULL, ['customer', 'month']);

	}

	public static function getGlobalTurnover(\farm\Farm $eFarm, array $years, ?int $month, ?string $week): \Collection {

		self::filterSaleStats();

		return Sale::model()
			->select([
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'shipping' => new \Sql('SUM(shippingExcludingVat)', 'float'),
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
			])
			->whereFarm($eFarm)
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'IN', $years)
			->where($month ? 'EXTRACT(MONTH FROM deliveredAt) = '.$month : NULL) 
			->where($week ? 'WEEK(deliveredAt, 1) = '.week_number($week) : NULL)
			->group(new \Sql('year'))
			->sort(new \Sql('year DESC'))
			->getCollection(index: 'year');

	}

	public static function getShopTurnover(\shop\Shop $eShop, array $years, ?int $month, ?string $week): \Collection {

		self::filterSaleStats();

		return Sale::model()
			->select([
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'shipping' => new \Sql('SUM(shippingExcludingVat)', 'float'),
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
			])
			->whereShop($eShop)
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'IN', $years)
			->where($month ? 'EXTRACT(MONTH FROM deliveredAt) = '.$month : NULL)
			->where($week ? 'WEEK(deliveredAt, 1) = '.week_number($week) : NULL)
			->group(new \Sql('year'))
			->sort(new \Sql('year DESC'))
			->getCollection(index: 'year');

	}

	public static function getCustomerTurnover(Customer $eCustomer, ?int $year = NULL): \Collection {

		if($year !== NULL and currentYear() - $year > 2) {
			$min = $year - 2;
			$max = $year + 2;
			Sale::model()->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'BETWEEN', new \Sql($min.' AND '.$max));
		}

		self::filterSaleStats();

		$mSaleInner = new SaleModel();
		self::filterSaleStats($mSaleInner);

		return Sale::model()
			->select([
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'turnoverGlobal' => $mSaleInner
					->select([
						'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int')
					])
					->group(new \Sql('year'))
					->delegateProperty('year', new \Sql('SUM(priceExcludingVat)', 'float'), propertyParent: 'year'),
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
			])
			->whereCustomer($eCustomer)
			->group(new \Sql('year'))
			->sort(new \Sql('year DESC'))
			->getCollection(0, 5);

	}

	public static function getProductTurnover(Product $eProduct, ?int $year = NULL, \Search $search = new \Search()): \Collection {

		return self::getProductsTurnover(new \Collection([$eProduct]), $year, $search);

	}

	public static function getProductsTurnover(\Collection $cProduct, ?int $year = NULL, \Search $search = new \Search()): \Collection {

		if($year !== NULL and currentYear() - $year > 2) {
			$min = $year - 2;
			$max = $year + 2;
			Sale::model()->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'BETWEEN', new \Sql($min.' AND '.$max));
		}

		$search->filter('type', fn($type) => Item::model()->whereType($type));

		self::filterItemStats();
		self::filterSaleStats();

		return Item::model()
			->select([
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'turnoverGlobal' => (new SaleModel())
					->select([
						'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'float')
					])
					->group(new \Sql('year'))
					->delegateProperty('year', new \Sql('SUM(priceExcludingVat)', 'float'), propertyParent: 'year'),
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
			])
			->whereProduct('IN', $cProduct)
			->group(new \Sql('year'))
			->sort(new \Sql('year DESC'))
			->getCollection(0, 5);

	}

	public static function getFarmProducts(\farm\Farm $eFarm, int $year, ?int $month, ?string $week, \Search $search): \Collection {

		Item::model()->where('m1.farm', $eFarm);

		return self::getProducts($year, $month, $week, $search);

	}

	public static function getShopProducts(\shop\Shop $eShop, int $year, ?int $month, ?string $week): \Collection {

		Item::model()->whereShop($eShop);

		return self::getProducts($year, $month, $week);

	}

	public static function getCustomerProducts(Customer $eCustomer, int $year, ?int $month = NULL, ?string $week = NULL): \Collection {

		Item::model()->whereCustomer($eCustomer);

		return self::getProducts($year, $month, $week);

	}

	public static function addShipping(\Collection $cSaleTurnover, \Collection $cItemProduct, int $year) {

		if(
			$cSaleTurnover->offsetExists($year) and
			$cSaleTurnover[$year]['shipping'] !== NULL
		) {
			$cItemProduct[] = new \selling\Item([
				'product' => new \selling\Product([
					'id' => NULL,
					'name' => \selling\SaleUi::getShippingName()
				]),
				'turnover' => $cSaleTurnover[$year]['shipping'],
				'unit' => NULL,
				'quantity' => NULL,
				'average' => NULL,
			]);
			$cItemProduct->sort([
				'turnover' => SORT_DESC
			]);
		}

	}

	private static function getProducts(int $year, ?int $month, ?string $week, \Search $search = new \Search()): \Collection {

		if($search->get('type')) {
			Item::model()->where('m2.type', $search->get('type'));
		}

		self::filterItemStats(TRUE);

		return Item::model()
			->select([
				'product' => ['vignette', 'name', 'variety'],
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'unit',
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'average' => new \Sql('SUM(priceExcludingVat) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float')
			])
			->join(Customer::model(), 'm1.customer = m2.id')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->where('number != 0')
			->where($month ? 'EXTRACT(MONTH FROM deliveredAt) = '.$month : NULL)
			->where($week ? 'WEEK(deliveredAt, 1) = '.week_number($week) : NULL)
			->whereProduct('!=', NULL)
			->group(['product', 'unit'])
			->sort(new \Sql('m1_turnover DESC'))
			->getCollection(index: fn($eItem) => $eItem['product']['id'].'-'.$eItem['unit']);

	}

	public static function filterItemStats(bool $join = FALSE, ?ItemModel $mItem = NULL) {

		$mItem ??= Item::model();

		if($join) {

			$mItem
				->where('m1.status', Sale::DELIVERED)
				->where('m1.stats', TRUE)
				->where('m1.priceExcludingVat', '!=', NULL);

		} else {

			$mItem
				->whereStatus(Sale::DELIVERED)
				->whereStats(TRUE)
				->wherePriceExcludingVat('!=', NULL);

		}


	}

	public static function filterSaleStats(?SaleModel $mSale = NULL) {

		$mSale ??= Sale::model();

		$mSale
			->wherePreparationStatus(Sale::DELIVERED)
			->whereStats(TRUE)
			->wherePriceExcludingVat('!=', NULL);

	}

	public static function getMonthlyFarmProducts(\farm\Farm $eFarm, int $year, \Search $search): \Collection {

		Item::model()->where('m1.farm', $eFarm);

		return self::getMonthlyProducts($year, $search);

	}

	public static function getMonthlyShopProducts(\shop\Shop $eShop, int $year): \Collection {

		Item::model()->whereShop($eShop);

		return self::getMonthlyProducts($year);

	}

	private static function getMonthlyProducts(int $year, \Search $search = new \Search()): \Collection {

		if($search->get('type')) {
			Item::model()->where('m2.type', $search->get('type'));
		}

		self::filterItemStats(TRUE);

		return Item::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM deliveredAt)', 'int'),
				'product',
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'average' => new \Sql('SUM(priceExcludingVat) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float')
			])
			->join(Customer::model(), 'm1.customer = m2.id')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->where('number != 0')
			->whereProduct('!=', NULL)
			->group(new \Sql('m1_product, m1.unit, m1_month'))
			->getCollection(index: ['product', 'month']);

	}

	public static function getFarmPlants(\farm\Farm $eFarm, int $year, ?int $month, ?string $week, \Search $search): \Collection {

		Item::model()->where('m1.farm', $eFarm);

		return self::getPlants($year, $month, $week, $search);

	}

	public static function getShopPlants(\shop\Shop $eShop, int $year, ?int $month, ?string $week): \Collection {

		Item::model()->where('m1.shop', $eShop);

		return self::getPlants($year, $month, $week);

	}

	public static function getPlants(int $year, ?int $month = NULL, ?string $week = NULL, \Search $search = new \Search()): \Collection {

		if($search->get('type')) {
			Item::model()->where('m3.type', $search->get('type'));
		}

		if($search->get('plant')) {
			Item::model()->where('m2.plant', $search->get('plant'));
		}

		self::filterItemStats(TRUE);

		$ccItemPlant = Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'average' => new \Sql('SUM(priceExcludingVat) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float')
			])
			->join(Product::model()
				->select([
					'plant' => ['vignette', 'fqn', 'name'],
					'unit'
				]), 'm1.product = m2.id')
			->join(Customer::model(), 'm1.customer = m3.id')
			->where('number != 0')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->where($month ? 'EXTRACT(MONTH FROM deliveredAt) = '.$month : NULL)
			->where($week ? 'WEEK(deliveredAt, 1) = '.week_number($week) : NULL)
			->where('m1.product', '!=', NULL)
			->where('m2.plant', '!=', NULL)
			->group(new \Sql('m2_plant, m2_unit'))
			->sort(new \Sql('m1_turnover DESC'))
			->getCollection(NULL, NULL, ['plant', NULL]);

		$cPlant = new \Collection();

		foreach($ccItemPlant as $cItemPlant) {

			$ePlant = $cItemPlant->first()['plant'];
			$ePlant['turnover'] = $cItemPlant->sum('turnover');
			$ePlant['cItem'] = $cItemPlant;

			$cPlant[$ePlant['id']] = $ePlant;

		}

		$cPlant->sort(['turnover' => SORT_DESC]);

		return $cPlant;

	}

	public static function getMonthlyFarmPlants(\farm\Farm $eFarm, int $year, \Search $search): \Collection {

		Item::model()->where('m1.farm', $eFarm);

		return self::getMonthlyPlants($year, $search);

	}

	public static function getMonthlyShopPlants(\shop\Shop $eShop, int $year): \Collection {

		Item::model()->where('m1.shop', $eShop);

		return self::getMonthlyPlants($year);

	}

	public static function getMonthlyPlants(int $year, \Search $search = new \Search()): \Collection {

		if($search->get('type')) {
			Item::model()->where('m3.type', $search->get('type'));
		}

		self::filterItemStats(TRUE);

		$cccItemPlant = Item::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM deliveredAt)', 'int'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'average' => new \Sql('SUM(priceExcludingVat) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float')
			])
			->join(Product::model()
				->select([
					'plant',
					'unit'
				]), 'm1.product = m2.id')
			->join(Customer::model(), 'm1.customer = m3.id')
			->where('number != 0')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->where('m1.product', '!=', NULL)
			->where('m2.plant', '!=', NULL)
			->group(new \Sql('m2_plant, m2_unit, m1_month'))
			->getCollection(NULL, NULL, ['plant', 'month', 'unit']);

		return $cccItemPlant;

	}

	public static function getYears(\farm\Farm $eFarm): array {

		return \Cache::redis()->query(
			'farm-sales-years-'.$eFarm['id'].'-'.date('Y-m-d'),
			function() use ($eFarm) {

				self::filterSaleStats();

				return Sale::model()
					->select([
						'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
						'sales' => new \Sql('COUNT(*)', 'int')
					])
					->whereFarm($eFarm)
					->group(new \Sql('year'))
					->sort(new \Sql('year DESC'))
					->getCollection()
					->toArray(function($eSale) {
						return [$eSale['year'], $eSale['sales']];
					}, keys: TRUE);
			},
			86400
		);

	}

	public static function getExport(\farm\Farm $eFarm, int $year): array {

		self::filterItemStats();

		// Ajout des articles
		$data = Item::model()
			->select([
				'name',
				'product' => ['name', 'variety'],
				'sale' => ['document',  'type'],
				'customer' => ['name'],
				'quantity' => new \Sql('IF(packaging IS NULL, 1, packaging) * number', 'float'),
				'priceExcludingVat',
				'unit',
				'deliveredAt'
			])
			->whereFarm($eFarm)
			->where('number != 0')
			->where('EXTRACT(YEAR FROM deliveredAt) = '.$year)
			->sort('id')
			->getCollection()
			->toArray(function($eItem) {
				return [
					$eItem['name'],
					$eItem['product']->empty() ? '' : $eItem['product']->getName(),
					$eItem['sale']['document'],
					$eItem['customer']['name'],
					CustomerUi::getType($eItem['sale']),
					\util\DateUi::numeric($eItem['deliveredAt']),
					\util\TextUi::csvNumber($eItem['quantity']),
					\main\UnitUi::getSingular($eItem['unit'], noWrap: FALSE),
					\util\TextUi::csvNumber($eItem['priceExcludingVat'])
				];
			});

		// Ajout des frais de livraison
		self::filterSaleStats();

		foreach(Sale::model()
			->select([
				'document', 'type',
				'customer' => ['name'],
				'shippingExcludingVat',
				'deliveredAt'
			])
			->whereFarm($eFarm)
			->where('shipping IS NOT NULL')
			->where('EXTRACT(YEAR FROM deliveredAt) = '.$year)
			->getCollection() as $eSale) {

			$data[] = [
				SaleUi::getShippingName(),
				'',
				$eSale['document'],
				$eSale['customer']['name'],
				CustomerUi::getType($eSale),
				\util\DateUi::numeric($eSale['deliveredAt']),
				'',
				'',
				\util\TextUi::csvNumber($eSale['shippingExcludingVat'])
			];

		}

		usort($data, function($a, $b) {

			if($a[2] !== $b[2]) {
				return $a[2] < $b[2] ? -1 : 1;
			}

			return strcmp($a[0], $b[0]);

		});

		return $data;

	}

}
?>
