<?php
namespace farm;

abstract class FarmerElement extends \Element {

	use \FilterElement;

	private static ?FarmerModel $model = NULL;

	const ACTIVE = 'active';
	const CLOSED = 'closed';

	const INVITED = 'invited';
	const IN = 'in';
	const OUT = 'out';

	const SEASONAL = 'seasonal';
	const PERMANENT = 'permanent';
	const OWNER = 'owner';
	const OBSERVER = 'observer';

	const DAILY = 'daily';
	const WEEKLY = 'weekly';
	const YEARLY = 'yearly';

	const TIME = 'time';
	const TEAM = 'team';
	const PACE = 'pace';
	const PERIOD = 'period';

	const TOTAL = 'total';

	const VARIETY = 'variety';
	const SOIL = 'soil';

	const AREA = 'area';
	const LENGTH = 'length';

	const PLANT = 'plant';
	const FAMILY = 'family';

	const HARVESTING = 'harvesting';
	const WORKING_TIME = 'working-time';

	const PLAN = 'plan';
	const ROTATION = 'rotation';

	const WHITE = 'white';
	const BLACK = 'black';

	const ALL = 'all';
	const PRIVATE = 'private';
	const PRO = 'pro';
	const MARKET = 'market';
	const LABEL = 'label';

	const PRODUCT = 'product';
	const CATEGORY = 'category';

	const CUSTOMER = 'customer';
	const GROUP = 'group';

	const ITEM = 'item';
	const SHOP = 'shop';

	const CAMPAIGN = 'campaign';
	const CONTACT = 'contact';

	const TURNOVER = 'turnover';
	const QUANTITY = 'quantity';

	const COMPOSITION = 'composition';
	const INGREDIENT = 'ingredient';

	public static function getSelection(): array {
		return Farmer::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): FarmerModel {
		if(self::$model === NULL) {
			self::$model = new FarmerModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Farmer::'.$failName, $arguments, $wrapper);
	}

}


class FarmerModel extends \ModuleModel {

	protected string $module = 'farm\Farmer';
	protected string $package = 'farm';
	protected string $table = 'farmFarmer';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'farmGhost' => ['bool', 'cast' => 'bool'],
			'farmStatus' => ['enum', [\farm\Farmer::ACTIVE, \farm\Farmer::CLOSED], 'cast' => 'enum'],
			'status' => ['enum', [\farm\Farmer::INVITED, \farm\Farmer::IN, \farm\Farmer::OUT], 'cast' => 'enum'],
			'role' => ['enum', [\farm\Farmer::SEASONAL, \farm\Farmer::PERMANENT, \farm\Farmer::OWNER, \farm\Farmer::OBSERVER], 'null' => TRUE, 'cast' => 'enum'],
			'viewPlanning' => ['enum', [\farm\Farmer::DAILY, \farm\Farmer::WEEKLY, \farm\Farmer::YEARLY], 'cast' => 'enum'],
			'viewPlanningAction' => ['element32', 'farm\Action', 'null' => TRUE, 'cast' => 'element'],
			'viewPlanningPlant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'viewPlanningUser' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'viewPlanningCategory' => ['enum', [\farm\Farmer::TIME, \farm\Farmer::TEAM, \farm\Farmer::PACE, \farm\Farmer::PERIOD], 'cast' => 'enum'],
			'viewPlanningYear' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'viewPlanningHarvestExpected' => ['enum', [\farm\Farmer::TOTAL, \farm\Farmer::WEEKLY], 'cast' => 'enum'],
			'viewPlanningField' => ['enum', [\farm\Farmer::VARIETY, \farm\Farmer::SOIL], 'cast' => 'enum'],
			'viewPlanningArea' => ['enum', [\farm\Farmer::AREA, \farm\Farmer::LENGTH], 'cast' => 'enum'],
			'viewPlanningUserDaily' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'viewCultivationCategory' => ['enum', [\farm\Farmer::AREA, \farm\Farmer::PLANT, \farm\Farmer::FAMILY], 'cast' => 'enum'],
			'viewSeries' => ['enum', [\farm\Farmer::AREA, \farm\Farmer::HARVESTING, \farm\Farmer::WORKING_TIME], 'cast' => 'enum'],
			'viewSoil' => ['enum', [\farm\Farmer::PLAN, \farm\Farmer::ROTATION], 'cast' => 'enum'],
			'viewSoilColor' => ['enum', [\farm\Farmer::WHITE, \farm\Farmer::BLACK, \farm\Farmer::PLANT], 'cast' => 'enum'],
			'viewSoilOverlay' => ['bool', 'cast' => 'bool'],
			'viewSoilTasks' => ['bool', 'cast' => 'bool'],
			'viewSellingSales' => ['enum', [\farm\Farmer::ALL, \farm\Farmer::PRIVATE, \farm\Farmer::PRO, \farm\Farmer::MARKET, \farm\Farmer::LABEL], 'cast' => 'enum'],
			'viewSellingProducts' => ['enum', [\farm\Farmer::PRODUCT, \farm\Farmer::CATEGORY], 'cast' => 'enum'],
			'viewSellingCustomers' => ['enum', [\farm\Farmer::CUSTOMER, \farm\Farmer::GROUP], 'cast' => 'enum'],
			'viewSellingCategory' => ['enum', [\farm\Farmer::ITEM, \farm\Farmer::CUSTOMER, \farm\Farmer::SHOP, \farm\Farmer::PERIOD], 'cast' => 'enum'],
			'viewSellingCategoryCurrent' => ['element32', 'selling\Category', 'null' => TRUE, 'cast' => 'element'],
			'viewSellingPreparing' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'viewMailingCategory' => ['enum', [\farm\Farmer::CAMPAIGN, \farm\Farmer::CONTACT], 'cast' => 'enum'],
			'viewShopCatalogCurrent' => ['element32', 'shop\Catalog', 'null' => TRUE, 'cast' => 'element'],
			'viewAnalyzeChart' => ['enum', [\farm\Farmer::TURNOVER, \farm\Farmer::QUANTITY], 'cast' => 'enum'],
			'viewAnalyzeComposition' => ['enum', [\farm\Farmer::COMPOSITION, \farm\Farmer::INGREDIENT], 'cast' => 'enum'],
			'viewAnalyzeYear' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'viewAccountingYear' => ['element32', 'account\FinancialYear', 'null' => TRUE, 'cast' => 'element'],
			'viewAccountingCashRegister' => ['element32', 'cash\Register', 'null' => TRUE, 'cast' => 'element'],
			'viewSeason' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'viewShopCurrent' => ['element32', 'shop\Shop', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'farm', 'farmGhost', 'farmStatus', 'status', 'role', 'viewPlanning', 'viewPlanningAction', 'viewPlanningPlant', 'viewPlanningUser', 'viewPlanningCategory', 'viewPlanningYear', 'viewPlanningHarvestExpected', 'viewPlanningField', 'viewPlanningArea', 'viewPlanningUserDaily', 'viewCultivationCategory', 'viewSeries', 'viewSoil', 'viewSoilColor', 'viewSoilOverlay', 'viewSoilTasks', 'viewSellingSales', 'viewSellingProducts', 'viewSellingCustomers', 'viewSellingCategory', 'viewSellingCategoryCurrent', 'viewSellingPreparing', 'viewMailingCategory', 'viewShopCatalogCurrent', 'viewAnalyzeChart', 'viewAnalyzeComposition', 'viewAnalyzeYear', 'viewAccountingYear', 'viewAccountingCashRegister', 'viewSeason', 'viewShopCurrent', 'createdAt'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'farm' => 'farm\Farm',
			'viewPlanningAction' => 'farm\Action',
			'viewPlanningPlant' => 'plant\Plant',
			'viewPlanningUser' => 'user\User',
			'viewPlanningUserDaily' => 'user\User',
			'viewSellingCategoryCurrent' => 'selling\Category',
			'viewShopCatalogCurrent' => 'shop\Catalog',
			'viewAccountingYear' => 'account\FinancialYear',
			'viewAccountingCashRegister' => 'cash\Register',
			'viewShopCurrent' => 'shop\Shop',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'farmGhost' :
				return FALSE;

			case 'farmStatus' :
				return Farmer::ACTIVE;

			case 'status' :
				return Farmer::INVITED;

			case 'viewPlanning' :
				return Farmer::WEEKLY;

			case 'viewPlanningCategory' :
				return Farmer::TIME;

			case 'viewPlanningHarvestExpected' :
				return Farmer::TOTAL;

			case 'viewPlanningField' :
				return Farmer::VARIETY;

			case 'viewPlanningArea' :
				return Farmer::LENGTH;

			case 'viewCultivationCategory' :
				return Farmer::AREA;

			case 'viewSeries' :
				return Farmer::AREA;

			case 'viewSoil' :
				return Farmer::PLAN;

			case 'viewSoilColor' :
				return Farmer::WHITE;

			case 'viewSoilOverlay' :
				return TRUE;

			case 'viewSoilTasks' :
				return FALSE;

			case 'viewSellingSales' :
				return Farmer::ALL;

			case 'viewSellingProducts' :
				return Farmer::PRODUCT;

			case 'viewSellingCustomers' :
				return Farmer::CUSTOMER;

			case 'viewSellingCategory' :
				return Farmer::ITEM;

			case 'viewMailingCategory' :
				return Farmer::CAMPAIGN;

			case 'viewAnalyzeChart' :
				return Farmer::TURNOVER;

			case 'viewAnalyzeComposition' :
				return Farmer::COMPOSITION;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'farmStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'role' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewPlanning' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewPlanningCategory' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewPlanningHarvestExpected' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewPlanningField' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewPlanningArea' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewCultivationCategory' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSeries' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSoil' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSoilColor' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSellingSales' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSellingProducts' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSellingCustomers' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSellingCategory' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSellingPreparing' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

			case 'viewMailingCategory' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewAnalyzeChart' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewAnalyzeComposition' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'viewSellingPreparing' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): FarmerModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FarmerModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): FarmerModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): FarmerModel {
		return $this->where('user', ...$data);
	}

	public function whereFarm(...$data): FarmerModel {
		return $this->where('farm', ...$data);
	}

	public function whereFarmGhost(...$data): FarmerModel {
		return $this->where('farmGhost', ...$data);
	}

	public function whereFarmStatus(...$data): FarmerModel {
		return $this->where('farmStatus', ...$data);
	}

	public function whereStatus(...$data): FarmerModel {
		return $this->where('status', ...$data);
	}

	public function whereRole(...$data): FarmerModel {
		return $this->where('role', ...$data);
	}

	public function whereViewPlanning(...$data): FarmerModel {
		return $this->where('viewPlanning', ...$data);
	}

	public function whereViewPlanningAction(...$data): FarmerModel {
		return $this->where('viewPlanningAction', ...$data);
	}

	public function whereViewPlanningPlant(...$data): FarmerModel {
		return $this->where('viewPlanningPlant', ...$data);
	}

	public function whereViewPlanningUser(...$data): FarmerModel {
		return $this->where('viewPlanningUser', ...$data);
	}

	public function whereViewPlanningCategory(...$data): FarmerModel {
		return $this->where('viewPlanningCategory', ...$data);
	}

	public function whereViewPlanningYear(...$data): FarmerModel {
		return $this->where('viewPlanningYear', ...$data);
	}

	public function whereViewPlanningHarvestExpected(...$data): FarmerModel {
		return $this->where('viewPlanningHarvestExpected', ...$data);
	}

	public function whereViewPlanningField(...$data): FarmerModel {
		return $this->where('viewPlanningField', ...$data);
	}

	public function whereViewPlanningArea(...$data): FarmerModel {
		return $this->where('viewPlanningArea', ...$data);
	}

	public function whereViewPlanningUserDaily(...$data): FarmerModel {
		return $this->where('viewPlanningUserDaily', ...$data);
	}

	public function whereViewCultivationCategory(...$data): FarmerModel {
		return $this->where('viewCultivationCategory', ...$data);
	}

	public function whereViewSeries(...$data): FarmerModel {
		return $this->where('viewSeries', ...$data);
	}

	public function whereViewSoil(...$data): FarmerModel {
		return $this->where('viewSoil', ...$data);
	}

	public function whereViewSoilColor(...$data): FarmerModel {
		return $this->where('viewSoilColor', ...$data);
	}

	public function whereViewSoilOverlay(...$data): FarmerModel {
		return $this->where('viewSoilOverlay', ...$data);
	}

	public function whereViewSoilTasks(...$data): FarmerModel {
		return $this->where('viewSoilTasks', ...$data);
	}

	public function whereViewSellingSales(...$data): FarmerModel {
		return $this->where('viewSellingSales', ...$data);
	}

	public function whereViewSellingProducts(...$data): FarmerModel {
		return $this->where('viewSellingProducts', ...$data);
	}

	public function whereViewSellingCustomers(...$data): FarmerModel {
		return $this->where('viewSellingCustomers', ...$data);
	}

	public function whereViewSellingCategory(...$data): FarmerModel {
		return $this->where('viewSellingCategory', ...$data);
	}

	public function whereViewSellingCategoryCurrent(...$data): FarmerModel {
		return $this->where('viewSellingCategoryCurrent', ...$data);
	}

	public function whereViewSellingPreparing(...$data): FarmerModel {
		return $this->where('viewSellingPreparing', ...$data);
	}

	public function whereViewMailingCategory(...$data): FarmerModel {
		return $this->where('viewMailingCategory', ...$data);
	}

	public function whereViewShopCatalogCurrent(...$data): FarmerModel {
		return $this->where('viewShopCatalogCurrent', ...$data);
	}

	public function whereViewAnalyzeChart(...$data): FarmerModel {
		return $this->where('viewAnalyzeChart', ...$data);
	}

	public function whereViewAnalyzeComposition(...$data): FarmerModel {
		return $this->where('viewAnalyzeComposition', ...$data);
	}

	public function whereViewAnalyzeYear(...$data): FarmerModel {
		return $this->where('viewAnalyzeYear', ...$data);
	}

	public function whereViewAccountingYear(...$data): FarmerModel {
		return $this->where('viewAccountingYear', ...$data);
	}

	public function whereViewAccountingCashRegister(...$data): FarmerModel {
		return $this->where('viewAccountingCashRegister', ...$data);
	}

	public function whereViewSeason(...$data): FarmerModel {
		return $this->where('viewSeason', ...$data);
	}

	public function whereViewShopCurrent(...$data): FarmerModel {
		return $this->where('viewShopCurrent', ...$data);
	}

	public function whereCreatedAt(...$data): FarmerModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class FarmerCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Farmer {

		$e = new Farmer();

		if(empty($id)) {
			Farmer::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Farmer::getSelection();
		}

		if(Farmer::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getByIds(mixed $ids, array $properties = [], mixed $sort = NULL, mixed $index = NULL): \Collection {

		if(empty($ids)) {
			return new \Collection();
		}

		if($properties === []) {
			$properties = Farmer::getSelection();
		}

		if($sort !== NULL) {
			Farmer::model()->sort($sort);
		}

		return Farmer::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Farmer {

		return new Farmer($properties);

	}

	public static function create(Farmer $e): void {

		Farmer::model()->insert($e);

	}

	public static function update(Farmer $e, array $properties): void {

		$e->expects(['id']);

		Farmer::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Farmer $e, array $properties): void {

		Farmer::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Farmer $e): void {

		$e->expects(['id']);

		Farmer::model()->delete($e);

	}

}


class FarmerPage extends \ModulePage {

	protected string $module = 'farm\Farmer';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FarmerLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FarmerLib::getPropertiesUpdate()
		);
	}

}
?>