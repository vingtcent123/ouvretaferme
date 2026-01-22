<?php
namespace selling;

class AnalyzeLib {

	public static function getFarmMonths(\farm\Farm $eFarm, int $year, \Search $search): \Collection {

		Item::model()->whereFarm($eFarm);

		return self::getMonths($eFarm, $year, $search);

	}

	public static function getCustomerMonths(Customer $eCustomer, int $year): \Collection {

		Item::model()->whereCustomer($eCustomer);

		return self::getMonths($eCustomer['farm'], $year);

	}

	public static function getProductMonths(Product $eProduct, int $year, \Search $search = new \Search()): \Collection {

		if($eProduct['profile'] === Product::COMPOSITION) {
			$search->set('doNotFilterComposition', TRUE);
		}

		Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->whereProduct($eProduct)
			->where('number != 0');

		return self::getMonths($eProduct['farm'], $year, $search);

	}

	public static function getProductsMonths(\Collection $cProduct, int $year, \Search $search = new \Search()): \Collection {

		if($cProduct->empty()) {
			return new \Collection();
		}

		if($search->get('type')) {
			Item::model()->where('m2.type', $search->get('type'));
		}

		self::filterItemStats(TRUE);

		$ccItem = Item::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM deliveredAt)', 'int'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'turnover' => new \Sql('SUM(priceStats)', 'float'),
				'unit' => \selling\Unit::getSelection(),
				'average' => new \Sql('SUM(priceStats) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float')
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

	private static function getMonths(\farm\Farm $eFarm, int $year, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->whereType($type));

		self::filterItemStats();

		if($search->get('doNotFilterComposition') !== TRUE) {
			self::filterItemComposition($eFarm);
		}

		return Item::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM deliveredAt)', 'int'),
				'products' => new \Sql('COUNT(DISTINCT product)'),
				'customers' => new \Sql('COUNT(DISTINCT customer)'),
				'turnover' => new \Sql('SUM(priceStats)', 'float'),
				'turnoverPrivate' => new \Sql('SUM(IF(type="'.Customer::PRIVATE.'", priceStats, 0))', 'float'),
				'turnoverPro' => new \Sql('SUM(IF(type="'.Customer::PRO.'", priceStats, 0))', 'float')
			])
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->group(new \Sql('month'))
			->sort(new \Sql('month DESC'))
			->getCollection(NULL, NULL, ['month']);

	}

	public static function getFarmWeeks(\farm\Farm $eFarm, int $year, \Search $search): \Collection {

		Item::model()->whereFarm($eFarm);

		return self::getWeeks($eFarm, $year, $search);

	}

	public static function getCustomerWeeks(Customer $eCustomer, int $year): \Collection {

		Item::model()->whereCustomer($eCustomer);

		return self::getWeeks($eCustomer['farm'], $year);

	}

	public static function getProductWeeks(Product $eProduct, int $year, \Search $search = new \Search()): \Collection {

		if($eProduct['profile'] === Product::COMPOSITION) {
			$search->set('doNotFilterComposition', TRUE);
		}

		Item::model()
			->whereProduct($eProduct)
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->where('number != 0');

		return self::getWeeks($eProduct['farm'], $year, $search);

	}

	public static function getProductsWeeks(\farm\Farm $eFarm, \Collection $cProduct, int $year, \Search $search = new \Search()): \Collection {

		Item::model()
			->whereProduct('IN', $cProduct)
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->where('number != 0');

		return self::getWeeks($eFarm, $year, $search);

	}

	private static function getWeeks(\farm\Farm $eFarm, int $year, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->whereType($type));

		self::filterItemStats();

		if($search->get('doNotFilterComposition') !== TRUE) {
			self::filterItemComposition($eFarm);
		}

		return Item::model()
			->select([
				'week' => new \Sql('WEEK(deliveredAt, 1)', 'int'),
				'turnover' => new \Sql('SUM(priceStats)', 'float')
			])
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->group(new \Sql('week'))
			->sort(new \Sql('week DESC'))
			->getCollection(NULL, NULL, ['week']);

	}

	public static function getFarmCustomers(\farm\Farm $eFarm, int $year, ?int $month, ?string $week, \Search $search): \Collection {

		Item::model()->where('m1.farm', $eFarm);

		return self::getCustomers($eFarm, $year, $month, $week, $search);

	}

	public static function getShopCustomers(\shop\Shop $eShop, \farm\Farm $eFarm, int $year, ?int $month, ?string $week): \Collection {

		Item::model()->whereShop($eShop);

		return self::getCustomers($eFarm, $year, $month, $week);

	}

	public static function getProductCustomers(Product $eProduct, int $year, \Search $search = new \Search()): \Collection {

		if($eProduct['profile'] === Product::COMPOSITION) {
			$search->set('doNotFilterComposition', TRUE);
		}

		return self::getProductsCustomers($eProduct['farm'], new \Collection([$eProduct]), $year, $search);
	}

	public static function getProductsCustomers(\farm\Farm $eFarm, \Collection $cProduct, int $year, \Search $search = new \Search()): \Collection {

		Item::model()->whereProduct('IN', $cProduct);

		Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
			])
			->where('number != 0');

		return self::getCustomers($eFarm, $year, NULL, NULL, $search);

	}

	private static function getCustomers(\farm\Farm $eFarm, int $year, ?int $month, ?string $week, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->where('m2.type', $type));

		self::filterItemStats(TRUE);

		if($search->get('doNotFilterComposition') !== TRUE) {
			self::filterItemComposition($eFarm);
		}

		return Item::model()
			->select([
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
				'customer' => ['type', 'name'],
				'turnover' => new \Sql('SUM(priceStats)', 'float')
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
				'turnover' => new \Sql('SUM(priceStats)', 'float')
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
				'turnover' => new \Sql('SUM(priceStats)', 'float')
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

	public static function getShopTurnover(\shop\Shop $eShop, \farm\Farm $eFarm, array $years, ?int $month, ?string $week): \Collection {

		self::filterSaleStats();

		return Sale::model()
			->select([
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'shipping' => new \Sql('SUM(shippingExcludingVat)', 'float'),
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
			])
			->whereShop($eShop)
			->whereFarm($eFarm)
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
					->whereFarm($eCustomer['farm'])
					->group(new \Sql('year'))
					->delegateProperty('year', new \Sql('SUM(priceExcludingVat)', 'float'), propertyParent: 'year'),
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
			])
			->whereCustomer($eCustomer)
			->group(new \Sql('year'))
			->sort(new \Sql('year DESC'))
			->getCollection(0, 5);

	}

	public static function getProductYear(\farm\Farm $eFarm, Product $eProduct, ?int $year = NULL, \Search $search = new \Search()): \Collection {

		if($eProduct['profile'] === Product::COMPOSITION) {
			$search->set('doNotFilterComposition', TRUE);
		}

		return self::getProductsYear($eFarm, new \Collection([$eProduct]), $year, $search);

	}

	public static function getProductsYear(\farm\Farm $eFarm, \Collection $cProduct, ?int $year = NULL, \Search $search = new \Search()): \Collection {

		if($cProduct->empty()) {
			return new \Collection();
		}

		if($year !== NULL and currentYear() - $year > 2) {
			$min = $year - 2;
			$max = $year + 2;
			Sale::model()->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'BETWEEN', new \Sql($min.' AND '.$max));
		}

		$search->filter('type', fn($type) => Item::model()->whereType($type));

		self::filterItemStats();
		self::filterSaleStats();

		if($search->get('doNotFilterComposition') !== TRUE) {
			self::filterItemComposition($eFarm);
		}

		return Item::model()
			->select([
				'turnover' => new \Sql('SUM(priceStats)', 'float'),
				'turnoverGlobal' => new SaleModel()
					->select([
						'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'float')
					])
					->whereFarm($cProduct->first()['farm'])
					->group(new \Sql('year'))
					->delegateProperty('year', new \Sql('SUM(priceExcludingVat)', 'float'), propertyParent: 'year'),
				'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
			])
			->whereProduct('IN', $cProduct)
			->group(new \Sql('year'))
			->sort(new \Sql('year DESC'))
			->getCollection(0, 5);

	}

	public static function getProductsTurnover(\farm\Farm $eFarm, \Collection $cProduct, int $year, \Search $search = new \Search()): \Collection {

		$search->filter('type', fn($type) => Item::model()->whereType($type));

		self::filterItemStats();
		self::filterSaleStats();
		self::filterItemComposition($eFarm);

		return Item::model()
			->select([
				'turnover' => new \Sql('SUM(priceStats)', 'float'),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'product'
			])
			->whereProduct('IN', $cProduct)
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->group('product')
			->sort(['turnover' => SORT_DESC])
			->getCollection(index: 'product');

	}

	public static function getSaleProducts(Sale $eSale, bool $displayExcludingVat = TRUE): \Collection {

		Item::model()->where('m1.sale', $eSale);

		return self::getProducts($eSale['farm'], field: $displayExcludingVat ? 'priceStats' : 'price');

	}

	public static function getFarmProducts(\farm\Farm $eFarm, int $year, ?int $month, ?string $week, \Search $search): \Collection {

		Item::model()->where('m1.farm', $eFarm);

		self::filterItemStats(TRUE);

		return self::getProducts($eFarm, $year, $month, $week, $search);

	}

	public static function getShopProducts(\shop\Shop $eShop, \farm\Farm $eFarm, int $year, ?int $month, ?string $week): \Collection {

		Item::model()->whereShop($eShop);

		self::filterItemStats(TRUE);

		return self::getProducts($eFarm, $year, $month, $week);

	}

	public static function getCustomerProducts(Customer $eCustomer, int $year, ?int $month = NULL, ?string $week = NULL): \Collection {

		Item::model()->whereCustomer($eCustomer);

		self::filterItemStats(TRUE);

		return self::getProducts($eCustomer['farm'], $year, $month, $week);

	}

	public static function addShipping(\Collection $cSaleTurnover, \Collection $cItemProduct, int $year) {

		if(
			$cSaleTurnover->offsetExists($year) and
			$cSaleTurnover[$year]['shipping'] !== NULL
		) {
			$cItemProduct[] = new \selling\Item([
				'product' => new \selling\Product([
					'id' => NULL,
					'name' => \selling\SaleUi::getShippingName(),
					'unprocessedVariety' => NULL,
					'mixedFrozen' => FALSE
				]),
				'turnover' => $cSaleTurnover[$year]['shipping'],
				'unit' => new Unit(),
				'quantity' => NULL,
				'average' => NULL,
				'containsComposition' => FALSE,
				'containsIngredient' => FALSE,
			]);
			$cItemProduct->sort([
				'turnover' => SORT_DESC
			]);
		}

	}

	private static function getProducts(\farm\Farm $eFarm, ?int $year = NULL, ?int $month = NULL, ?string $week = NULL, \Search $search = new \Search(), string $field = 'priceStats'): \Collection {

		if($search->get('type')) {
			Item::model()->where('m2.type', $search->get('type'));
		}

		self::filterItemComposition($eFarm);

		return Item::model()
			->select([
				'product' => ProductElement::getSelection(),
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'unit' => \selling\Unit::getSelection(),
				'turnover' => new \Sql('SUM('.$field.')', 'float'),
				'average' => new \Sql('SUM('.$field.') / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'containsComposition' => new \Sql('SUM(composition IS NOT NULL) > 0', 'bool'),
				'containsIngredient' => new \Sql('SUM(ingredientOf IS NOT NULL) > 0', 'bool')
			])
			->join(Customer::model(), 'm1.customer = m2.id')
			->where('m1.farm', $eFarm)
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year, if: $year)
			->where('number != 0')
			->where($month ? 'EXTRACT(MONTH FROM deliveredAt) = '.$month : NULL)
			->where($week ? 'WEEK(deliveredAt, 1) = '.week_number($week) : NULL)
			->whereProduct('!=', NULL)
			->group(['product', 'unit'])
			->sort(new \Sql('m1_turnover DESC'))
			->getCollection(index: fn($eItem) => $eItem['product']['id'].'-'.$eItem['unit']);

	}

	public static function filterItemComposition(\farm\Farm $eFarm, ?ItemModel $mItem = NULL) {

		$display = $eFarm->getView('viewAnalyzeComposition');

		($mItem ?? Item::model())
			->whereIngredientOf(NULL, if: $display === \farm\Farmer::COMPOSITION)
			->whereComposition(new Sale(), if: $display === \farm\Farmer::INGREDIENT);

	}

	public static function filterItemStats(bool $join = FALSE, ?ItemModel $mItem = NULL) {

		$mItem ??= Item::model();

		if($join) {

			$mItem
				->where('m1.status', Sale::DELIVERED)
				->where('m1.stats', TRUE)
				->where('m1.priceStats', '!=', NULL);

		} else {

			$mItem
				->whereStatus(Sale::DELIVERED)
				->whereStats(TRUE)
				->wherePriceStats('!=', NULL);

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

		return self::getMonthlyProducts($eFarm, $year, $search);

	}

	public static function getMonthlyShopProducts(\shop\Shop $eShop, \farm\Farm $eFarm, int $year): \Collection {

		Item::model()->whereShop($eShop);

		return self::getMonthlyProducts($eFarm, $year);

	}

	private static function getMonthlyProducts(\farm\Farm $eFarm, int $year, \Search $search = new \Search()): \Collection {

		if($search->get('type')) {
			Item::model()->where('m2.type', $search->get('type'));
		}

		self::filterItemStats(TRUE);
		self::filterItemComposition($eFarm);

		return Item::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM deliveredAt)', 'int'),
				'product',
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'turnover' => new \Sql('SUM(priceStats)', 'float'),
				'average' => new \Sql('SUM(priceStats) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float')
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

		return self::getPlants($eFarm, $year, $month, $week, $search);

	}

	public static function getShopPlants(\shop\Shop $eShop, \farm\Farm $eFarm, int $year, ?int $month, ?string $week): \Collection {

		Item::model()->where('m1.shop', $eShop);

		return self::getPlants($eFarm, $year, $month, $week);

	}

	public static function getPlants(\farm\Farm $eFarm, int $year, ?int $month = NULL, ?string $week = NULL, \Search $search = new \Search()): \Collection {

		if($search->get('type')) {
			Item::model()->where('m3.type', $search->get('type'));
		}

		if($search->get('plant')) {
			Item::model()->where('m2.unprocessedPlant', $search->get('plant'));
		}

		self::filterItemStats(TRUE);
		self::filterItemComposition($eFarm);

		$ccItemPlant = Item::model()
			->select([
				'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'turnover' => new \Sql('SUM(priceStats)', 'float'),
				'average' => new \Sql('SUM(priceStats) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
				'containsComposition' => new \Sql('SUM(composition IS NOT NULL) > 0', 'bool'),
				'containsIngredient' => new \Sql('SUM(ingredientOf IS NOT NULL) > 0', 'bool')
			])
			->join(Product::model()
				->select([
					'unprocessedPlant' => ['vignette', 'fqn', 'name'],
					'unit' => \selling\Unit::getSelection(),
				]), 'm1.product = m2.id')
			->join(Customer::model(), 'm1.customer = m3.id')
			->where('number != 0')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->where($month ? 'EXTRACT(MONTH FROM deliveredAt) = '.$month : NULL)
			->where($week ? 'WEEK(deliveredAt, 1) = '.week_number($week) : NULL)
			->where('m1.product', '!=', NULL)
			->where('m2.unprocessedPlant', '!=', NULL)
			->group(new \Sql('m2_unprocessedPlant, m2_unit'))
			->sort(new \Sql('m1_turnover DESC'))
			->getCollection(NULL, NULL, ['unprocessedPlant', NULL]);

		$cPlant = new \Collection();

		foreach($ccItemPlant as $cItemPlant) {

			$ePlant = $cItemPlant->first()['unprocessedPlant'];
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

	public static function getMonthlyShopPlants(\shop\Shop $eShop, \farm\Farm $eFarm, int $year): \Collection {

		Item::model()
			->where('m1.farm', $eFarm)
			->where('m1.shop', $eShop);

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
				'turnover' => new \Sql('SUM(priceStats)', 'float'),
				'average' => new \Sql('SUM(priceStats) / SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float')
			])
			->join(Product::model()
				->select([
					'unprocessedPlant',
					'unit' => \selling\Unit::getSelection(),
				]), 'm1.product = m2.id')
			->join(Customer::model(), 'm1.customer = m3.id')
			->where('number != 0')
			->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), $year)
			->where('m1.product', '!=', NULL)
			->where('m2.unprocessedPlant', '!=', NULL)
			->group(new \Sql('m2_unprocessedPlant, m2_unit, m1_month'))
			->getCollection(NULL, NULL, ['unprocessedPlant', 'month', 'unit']);

		return $cccItemPlant;

	}

	public static function getYears(\farm\Farm $eFarm): array {

		$key = 'farm-sales-'.$eFarm['id'].'-'.date('Y-m-d');

		$output = \Cache::redis()->get($key);

		if($output === FALSE) {

			self::filterSaleStats();

			$cSale = Sale::model()
				->select([
					'year' => new \Sql('EXTRACT(YEAR FROM deliveredAt)', 'int'),
					'sales' => new \Sql('COUNT(*)', 'int')
				])
				->whereFarm($eFarm)
				->group(new \Sql('year'))
				->sort(new \Sql('year DESC'))
				->getCollection();

			if($cSale->empty()) {
				return [
					[],
					[]
				];
			}

			$output = [
				$cSale->getColumn('year'),
				$cSale->toArray(function($eSale) {
					return [$eSale['year'], $eSale['sales']];
				}, keys: TRUE)
			];

			\Cache::redis()->set($key, $output, 86400);

		}

		return $output;

	}

	public static function getExportInvoices(\farm\Farm $eFarm, int $year): array {

		$cInvoice = Invoice::model()
			->select([
				'id', 'document',
				'farm',
				'name',
				'customer' => ['name', 'siret', 'vatNumber'],
				'priceIncludingVat', 'priceExcludingVat',
				'vat', 'vatByRate',
				'date', 'dueDate',
				'paymentMethod' => ['name'],
				'paymentStatus',
			])
			->whereFarm($eFarm)
			->whereStatus('NOT IN', [Invoice::DRAFT, Invoice::CONFIRMED, Invoice::CANCELED])
			->whereGeneration(Invoice::SUCCESS)
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->sort('id')
			->getCollection();

		$vatRates = $cInvoice->reduce(function($eInvoice, $vatRates) {

			if($eInvoice['vatByRate'] !== NULL) {
				return array_merge($vatRates, array_column($eInvoice['vatByRate'], 'vatRate'));
			} else {
				return $vatRates;
			}

		}, []);

		$vatRates = array_unique($vatRates);
		sort($vatRates);

		$data = $cInvoice->toArray(function(Invoice $eInvoice) use($eFarm, $vatRates) {

				$data = [
					$eInvoice->getInvoice($eInvoice['farm']),
					$eInvoice['customer']->getName(),
					$eInvoice['customer']['siret'],
					$eInvoice['customer']['vatNumber'],
					\util\DateUi::numeric($eInvoice['date']),
					$eInvoice['dueDate'] === NULL ? '' : \util\DateUi::numeric($eInvoice['dueDate']),
					$eInvoice['paymentMethod']->empty() ? '' : $eInvoice['paymentMethod']['name'],
					match($eInvoice['paymentStatus']) {
						Invoice::PAID => 'paid',
						Invoice::NOT_PAID => 'not_paid',
						NULL => ''
					},
					\util\TextUi::csvNumber($eInvoice['priceExcludingVat']),
				];

				if($vatRates) {

					$vatList = [];
					foreach($eInvoice['vatByRate'] as ['vat' => $vat, 'vatRate' => $vatRate]) {
						$vatList[(string)$vatRate] = $vat;
					}

					foreach($vatRates as $rate) {
						if(isset($vatList[(string)$rate])) {
							$data[] = \util\TextUi::csvNumber($vatList[(string)$rate]);
						} else {
							$data[] = '';
						}
					}

					$data[] = \util\TextUi::csvNumber($eInvoice['priceIncludingVat']);

				}

				$data[] = \Lime::getUrl().InvoiceUi::url($eInvoice);

				return $data;
			});

		return [$data, $vatRates];

	}

	public static function getExportSales(\farm\Farm $eFarm, int $year): array {

		self::filterSaleStats();

		$data = Sale::model()
			->select([
				'id',
				'document',
				'items', 'discount',
				'type',
				'invoice' => ['name'],
				'customer' => ['name'],
				'priceIncludingVat', 'priceExcludingVat', 'vat',
				'shop' => ['name'],
				'deliveredAt',
				'cPayment' => Payment::model()
					->select(Payment::getSelection())
					->or(
						fn() => $this->whereOnlineStatus(NULL),
						fn() => $this->whereOnlineStatus(Payment::SUCCESS)
					)
					->delegateCollection('sale', 'id'),
			])
			->whereFarm($eFarm)
			->where('EXTRACT(YEAR FROM deliveredAt) = '.$year)
			->sort('id')
			->getCollection()
			->toArray(function($eSale) use($eFarm) {

				$data = [
					$eSale['document'],
					$eSale['invoice']->notEmpty() ? $eSale['invoice']['name'] : '',
					$eSale['customer']->notEmpty() ? $eSale['customer']->getName() : '',
					CustomerUi::getType($eSale),
					\util\DateUi::numeric($eSale['deliveredAt']),
					$eSale['items'],
					$eSale['shop']->empty() ? '' : $eSale['shop']['name'],
					$eSale['cPayment']->empty() ? '' : $eSale['cPayment']->first()['method']['name'],
					\util\TextUi::csvNumber($eSale['priceExcludingVat']),
				];

				if($eFarm->getConf('hasVat')) {
					$data[] = \util\TextUi::csvNumber($eSale['vat']);
					$data[] = \util\TextUi::csvNumber($eSale['priceIncludingVat']);
				}

				return $data;
			});

		return $data;

	}

	public static function getExportItems(\farm\Farm $eFarm, int $year): array {

		self::filterItemStats();

		if($eFarm->hasAccounting()) {
			\farm\FarmLib::connectDatabase($eFarm);
			$cAccountAll = \account\AccountLib::getAll();
		} else {
			$cAccountAll = new \Collection();
		}

		// Ajout des articles
		$data = Item::model()
			->select([
				'id',
				'name',
				'product' => ProductElement::getSelection(),
				'composition',
				'ingredientOf',
				'sale' => ['document',  'type', 'invoice' => ['name']],
				'customer' => ['type', 'name'],
				'quantity' => new \Sql('IF(packaging IS NULL, 1, packaging) * number', 'float'),
				'type', 'price', 'priceStats', 'vatRate',
				'unit' => \selling\Unit::getSelection(),
				'deliveredAt',
				'account'
			])
			->whereFarm($eFarm)
			->where('number != 0')
			->where('EXTRACT(YEAR FROM deliveredAt) = '.$year)
			->sort('id')
			->getCollection()
			->toArray(function($eItem) use($eFarm, $cAccountAll) {

				$data = [
					$eItem['sale']['document'],
					$eItem['sale']['invoice']['name'] ?? '',
					$eItem['id'],
					$eItem['name'],
					$eItem['product']->empty() ? '' : $eItem['product']['id'],
					$eItem['ingredientOf']->notEmpty() ? 'ingredient' : ($eItem['composition']->notEmpty() ? 'composed' : 'simple'),
				];

				if($eFarm->hasAccounting()) {

					if($eItem['account']->notEmpty() and $cAccountAll->offsetExists($eItem['account']['id'])) {

						$data[] = \account\AccountLabelLib::pad($cAccountAll->offsetGet($eItem['account']['id'])['class']);

					// On remonte sur le produit si possible
					} else if($eItem['product']->notEmpty()) {

						if($eItem['type'] === Item::PRO) {

							if($eItem['product']['proAccount']->notEmpty() and $cAccountAll->offsetExists($eItem['product']['proAccount']['id'])) {

								$data[] = \account\AccountLabelLib::pad($cAccountAll->offsetGet($eItem['product']['proAccount']['id'])['class']);

							} else if($eItem['product']['privateAccount']->notEmpty() and $cAccountAll->offsetExists($eItem['product']['privateAccount']['id'])) {

								$data[] = \account\AccountLabelLib::pad($cAccountAll->offsetGet($eItem['product']['privateAccount']['id'])['class']);

							} else {
								$data[] = '';
							}

						} else {

							if($eItem['product']['privateAccount']->notEmpty() and $cAccountAll->offsetExists($eItem['product']['privateAccount']['id'])) {

								$data[] = \account\AccountLabelLib::pad($cAccountAll->offsetGet($eItem['product']['privateAccount']['id'])['class']);

							} else {
								$data[] = '';
							}

						}

					} else {
						$data[] = '';
					}
				}

				$data = array_merge($data, [$eItem['customer']->notEmpty() ? $eItem['customer']->getName() : '',
					CustomerUi::getType($eItem['sale']),
					\util\DateUi::numeric($eItem['deliveredAt']),
					\util\TextUi::csvNumber($eItem['quantity']),
					\selling\UnitUi::getSingular($eItem['unit'], noWrap: FALSE),
					\util\TextUi::csvNumber($eItem['priceStats']),
				]);

				if($eFarm->getConf('hasVat')) {
					$data[] = \util\TextUi::csvNumber($eItem['vatRate']);
					$data[] = match($eItem['type']) {
						Item::PRO => \util\TextUi::csvNumber($eItem['price'] * (1 + $eItem['vatRate'] / 100), 2),
						Item::PRIVATE => \util\TextUi::csvNumber($eItem['price']),
					};
				}

				return $data;
			});

		// Ajout des frais de livraison
		self::filterSaleStats();

		foreach(Sale::model()
			->select([
				'invoice' => ['name'],
				'document', 'type',
				'customer' => ['type', 'name'],
				'shippingExcludingVat',
				'deliveredAt'
			])
			->whereFarm($eFarm)
			->where('shipping IS NOT NULL')
			->where('EXTRACT(YEAR FROM deliveredAt) = '.$year)
			->getCollection() as $eSale) {

			$data[] = [
				$eSale['document'],
				$eSale['invoice']['name'] ?? '',
				'',
				SaleUi::getShippingName(),
				$eSale['customer']->getName(),
				CustomerUi::getType($eSale),
				\util\DateUi::numeric($eSale['deliveredAt']),
				'',
				'',
				\util\TextUi::csvNumber($eSale['shippingExcludingVat'])
			];

		}

		usort($data, function($a, $b) {

			if($a[0] !== $b[0]) {
				return $a[0] < $b[0] ? -1 : 1;
			}

			return strcmp($a[3], $b[3]);

		});

		return $data;

	}

	public static function getExportProducts(\farm\Farm $eFarm): array {

		$data = Product::model()
			->select(ProductElement::getSelection() + [
				'unprocessedPlant' => ['name'],
				'category' => ['name'],
				'unit' => \selling\Unit::getSelection(),
			])
			->whereFarm($eFarm)
			->whereStatus(Product::ACTIVE)
			->sort('name')
			->getCollection()
			->toArray(function($eProduct) use($eFarm) {
				return [
					$eProduct['profile'],
					$eProduct['name'],
					$eProduct['reference'],
					$eProduct['unit']->empty() ? '' : $eProduct['unit']['singular'],
					($eProduct['privatePrice'] !== NULL) ? \util\TextUi::csvNumber($eProduct['privatePrice']) : '',
					($eProduct['proPrice'] !== NULL) ? \util\TextUi::csvNumber($eProduct['proPrice']) : '',
					$eFarm->getConf('hasVat') ? \util\TextUi::csvNumber(SellingSetting::getVatRate($eFarm, $eProduct['vat'])) : '',
					$eProduct['additional'],
					$eProduct['origin'],
					$eProduct['quality'],
					$eProduct['unprocessedPlant']->empty() ? '' : $eProduct['unprocessedPlant']['name'],
					$eProduct['unprocessedVariety'],
					$eProduct['mixedFrozen'],
					$eProduct['processedPackaging'],
					$eProduct['processedComposition'],
					$eProduct['processedAllergen'],
				];
			});

		return $data;

	}

	public static function getExportCustomers(\farm\Farm $eFarm): array {

		$data = Customer::model()
			->select(Customer::getSelection() + [
				'contact' => \mail\Contact::model()
					->select(\mail\ContactElement::getSelection())
					->whereFarm($eFarm)
					->delegateElement('email', propertyParent: 'email')
			])
			->whereFarm($eFarm)
			->or(
				fn() => $this->whereType(Customer::PRO),
				fn() => $this
					->whereType(Customer::PRIVATE)
					->whereDestination(Customer::INDIVIDUAL)
			)
			->whereStatus(Customer::ACTIVE)
			->sort('name')
			->getCollection()
			->toArray(function($eCustomer) use($eFarm) {

				$cGroup = $eCustomer['cGroup?']();

				return [
					CustomerUi::getCategory($eCustomer),
					$eCustomer['type'] === Customer::PRIVATE ? $eCustomer['firstName'] : '',
					$eCustomer['type'] === Customer::PRIVATE ? $eCustomer['lastName'] : '',
					$eCustomer['type'] === Customer::PRO ? $eCustomer['commercialName'] : '',
					$eCustomer['type'] === Customer::PRO ? $eCustomer['legalName'] : '',
					$eCustomer['user']->empty() ? 'no' : 'yes',
					$eCustomer['email'],
					$eCustomer['phone'],
					$cGroup->notEmpty() ? implode(', ', $cGroup->getColumn('name')) : '',
					$eCustomer['type'] === Customer::PRO ? $eCustomer['contactName'] : '',
					$eCustomer['type'] === Customer::PRO ? $eCustomer['siret'] : '',
					$eCustomer['type'] === Customer::PRO ? $eCustomer['vatNumber'] : '',
					$eCustomer['invoiceStreet1'],
					$eCustomer['invoiceStreet2'],
					$eCustomer['invoicePostcode'],
					$eCustomer['invoiceCity'],
					$eCustomer['invoiceCountry']->notEmpty() ? \user\Country::ask($eCustomer['invoiceCountry'])['name'] : '',
					$eCustomer['deliveryStreet1'],
					$eCustomer['deliveryStreet2'],
					$eCustomer['deliveryPostcode'],
					$eCustomer['deliveryCity'],
					$eCustomer['deliveryCountry']->notEmpty() ? \user\Country::ask($eCustomer['deliveryCountry'])['name'] : '',
					$eCustomer['discount'],
					($eCustomer['contact']->getOptIn() === NULL) ? s("?") : ($eCustomer['contact']->getOptIn() ? 'yes' : 'no'),
				];
			});

		return $data;

	}

}
?>
