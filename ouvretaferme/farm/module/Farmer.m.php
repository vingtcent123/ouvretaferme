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
	const TEAM = 'team';

	const TIME = 'time';
	const PACE = 'pace';
	const PERIOD = 'period';

	const TOTAL = 'total';

	const VARIETY = 'variety';
	const SOIL = 'soil';

	const SERIES = 'series';
	const SEQUENCE = 'sequence';
	const PLANT = 'plant';

	const AREA = 'area';
	const FAMILY = 'family';
	const ROTATION = 'rotation';

	const FORECAST = 'forecast';
	const SEEDLING = 'seedling';
	const HARVESTING = 'harvesting';
	const WORKING_TIME = 'working-time';
	const TOOL = 'tool';

	const SALE = 'sale';
	const PRODUCT = 'product';
	const CUSTOMER = 'customer';
	const SHOP = 'shop';

	const ALL = 'all';
	const PRIVATE = 'private';
	const PRO = 'pro';
	const INVOICE = 'invoice';
	const LABEL = 'label';

	const ITEM = 'item';

	const CARTOGRAPHY = 'cartography';
	const HISTORY = 'history';

	const REPORT = 'report';
	const SALES = 'sales';
	const CULTIVATION = 'cultivation';

	const SETTINGS = 'settings';
	const WEBSITE = 'website';

	public static function getSelection(): array {
		return Farmer::model()->getProperties();
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
			'viewPlanning' => ['enum', [\farm\Farmer::DAILY, \farm\Farmer::WEEKLY, \farm\Farmer::YEARLY, \farm\Farmer::TEAM], 'cast' => 'enum'],
			'viewPlanningYear' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'viewPlanningCategory' => ['enum', [\farm\Farmer::TIME, \farm\Farmer::TEAM, \farm\Farmer::PACE, \farm\Farmer::PERIOD], 'cast' => 'enum'],
			'viewPlanningHarvestExpected' => ['enum', [\farm\Farmer::TOTAL, \farm\Farmer::WEEKLY], 'cast' => 'enum'],
			'viewPlanningField' => ['enum', [\farm\Farmer::VARIETY, \farm\Farmer::SOIL], 'cast' => 'enum'],
			'viewPlanningSearch' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'viewPlanningUser' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'viewCultivation' => ['enum', [\farm\Farmer::SERIES, \farm\Farmer::SEQUENCE, \farm\Farmer::PLANT], 'cast' => 'enum'],
			'viewCultivationCategory' => ['enum', [\farm\Farmer::AREA, \farm\Farmer::PLANT, \farm\Farmer::FAMILY, \farm\Farmer::ROTATION], 'cast' => 'enum'],
			'viewSeries' => ['enum', [\farm\Farmer::AREA, \farm\Farmer::FORECAST, \farm\Farmer::SEEDLING, \farm\Farmer::HARVESTING, \farm\Farmer::WORKING_TIME, \farm\Farmer::TOOL], 'cast' => 'enum'],
			'viewSelling' => ['enum', [\farm\Farmer::SALE, \farm\Farmer::PRODUCT, \farm\Farmer::CUSTOMER, \farm\Farmer::SHOP], 'cast' => 'enum'],
			'viewSellingSales' => ['enum', [\farm\Farmer::ALL, \farm\Farmer::PRIVATE, \farm\Farmer::PRO, \farm\Farmer::INVOICE, \farm\Farmer::LABEL], 'cast' => 'enum'],
			'viewSellingCategory' => ['enum', [\farm\Farmer::ITEM, \farm\Farmer::CUSTOMER, \farm\Farmer::SHOP, \farm\Farmer::PERIOD], 'cast' => 'enum'],
			'viewMap' => ['enum', [\farm\Farmer::CARTOGRAPHY, \farm\Farmer::SOIL, \farm\Farmer::HISTORY], 'cast' => 'enum'],
			'viewAnalyze' => ['enum', [\farm\Farmer::WORKING_TIME, \farm\Farmer::REPORT, \farm\Farmer::SALES, \farm\Farmer::CULTIVATION], 'cast' => 'enum'],
			'viewAnalyzeYear' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'viewSettings' => ['enum', [\farm\Farmer::SETTINGS, \farm\Farmer::WEBSITE], 'cast' => 'enum'],
			'viewSeason' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'viewShop' => ['element32', 'shop\Shop', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'farm', 'farmGhost', 'farmStatus', 'status', 'role', 'viewPlanning', 'viewPlanningYear', 'viewPlanningCategory', 'viewPlanningHarvestExpected', 'viewPlanningField', 'viewPlanningSearch', 'viewPlanningUser', 'viewCultivation', 'viewCultivationCategory', 'viewSeries', 'viewSelling', 'viewSellingSales', 'viewSellingCategory', 'viewMap', 'viewAnalyze', 'viewAnalyzeYear', 'viewSettings', 'viewSeason', 'viewShop', 'createdAt'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'farm' => 'farm\Farm',
			'viewPlanningUser' => 'user\User',
			'viewShop' => 'shop\Shop',
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
				return Farmer::SOIL;

			case 'viewCultivation' :
				return Farmer::SERIES;

			case 'viewCultivationCategory' :
				return Farmer::AREA;

			case 'viewSeries' :
				return Farmer::AREA;

			case 'viewSelling' :
				return Farmer::SALE;

			case 'viewSellingSales' :
				return Farmer::ALL;

			case 'viewSellingCategory' :
				return Farmer::ITEM;

			case 'viewMap' :
				return Farmer::CARTOGRAPHY;

			case 'viewAnalyze' :
				return Farmer::WORKING_TIME;

			case 'viewSettings' :
				return Farmer::SETTINGS;

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

			case 'viewPlanningSearch' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'viewCultivation' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewCultivationCategory' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSeries' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSelling' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSellingSales' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSellingCategory' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewMap' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewAnalyze' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'viewSettings' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'viewPlanningSearch' :
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

	public function whereViewPlanningYear(...$data): FarmerModel {
		return $this->where('viewPlanningYear', ...$data);
	}

	public function whereViewPlanningCategory(...$data): FarmerModel {
		return $this->where('viewPlanningCategory', ...$data);
	}

	public function whereViewPlanningHarvestExpected(...$data): FarmerModel {
		return $this->where('viewPlanningHarvestExpected', ...$data);
	}

	public function whereViewPlanningField(...$data): FarmerModel {
		return $this->where('viewPlanningField', ...$data);
	}

	public function whereViewPlanningSearch(...$data): FarmerModel {
		return $this->where('viewPlanningSearch', ...$data);
	}

	public function whereViewPlanningUser(...$data): FarmerModel {
		return $this->where('viewPlanningUser', ...$data);
	}

	public function whereViewCultivation(...$data): FarmerModel {
		return $this->where('viewCultivation', ...$data);
	}

	public function whereViewCultivationCategory(...$data): FarmerModel {
		return $this->where('viewCultivationCategory', ...$data);
	}

	public function whereViewSeries(...$data): FarmerModel {
		return $this->where('viewSeries', ...$data);
	}

	public function whereViewSelling(...$data): FarmerModel {
		return $this->where('viewSelling', ...$data);
	}

	public function whereViewSellingSales(...$data): FarmerModel {
		return $this->where('viewSellingSales', ...$data);
	}

	public function whereViewSellingCategory(...$data): FarmerModel {
		return $this->where('viewSellingCategory', ...$data);
	}

	public function whereViewMap(...$data): FarmerModel {
		return $this->where('viewMap', ...$data);
	}

	public function whereViewAnalyze(...$data): FarmerModel {
		return $this->where('viewAnalyze', ...$data);
	}

	public function whereViewAnalyzeYear(...$data): FarmerModel {
		return $this->where('viewAnalyzeYear', ...$data);
	}

	public function whereViewSettings(...$data): FarmerModel {
		return $this->where('viewSettings', ...$data);
	}

	public function whereViewSeason(...$data): FarmerModel {
		return $this->where('viewSeason', ...$data);
	}

	public function whereViewShop(...$data): FarmerModel {
		return $this->where('viewShop', ...$data);
	}

	public function whereCreatedAt(...$data): FarmerModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class FarmerCrud extends \ModuleCrud {

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

	public static function getCreateElement(): Farmer {

		return new Farmer(['id' => NULL]);

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