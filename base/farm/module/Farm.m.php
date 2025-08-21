<?php
namespace farm;

abstract class FarmElement extends \Element {

	use \FilterElement;

	private static ?FarmModel $model = NULL;

	const ORGANIC = 'organic';
	const NATURE_PROGRES = 'nature-progres';
	const CONVERSION = 'conversion';

	const ACTIVE = 'active';
	const CLOSED = 'closed';

	public static function getSelection(): array {
		return Farm::model()->getProperties();
	}

	public static function model(): FarmModel {
		if(self::$model === NULL) {
			self::$model = new FarmModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Farm::'.$failName, $arguments, $wrapper);
	}

}


class FarmModel extends \ModuleModel {

	protected string $module = 'farm\Farm';
	protected string $package = 'farm';
	protected string $table = 'farm';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'legalName' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'legalEmail' => ['email', 'null' => TRUE, 'cast' => 'string'],
			'siret' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'legalStreet1' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'legalStreet2' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'legalPostcode' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'legalCity' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'vignette' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'place' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'placeLngLat' => ['point', 'null' => TRUE, 'cast' => 'json'],
			'url' => ['url', 'null' => TRUE, 'cast' => 'string'],
			'description' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'logo' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'emailBanner' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'emailFooter' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'emailDefaultTime' => ['time', 'null' => TRUE, 'cast' => 'string'],
			'seasonFirst' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'seasonLast' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'rotationYears' => ['int8', 'min' => 2, 'max' => 5, 'cast' => 'int'],
			'rotationExclude' => ['json', 'cast' => 'array'],
			'quality' => ['enum', [\farm\Farm::ORGANIC, \farm\Farm::NATURE_PROGRES, \farm\Farm::CONVERSION], 'null' => TRUE, 'cast' => 'enum'],
			'defaultBedLength' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'defaultBedWidth' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'defaultAlleyWidth' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'calendarMonthStart' => ['int8', 'min' => 7, 'max' => 12, 'null' => TRUE, 'cast' => 'int'],
			'calendarMonthStop' => ['int8', 'min' => 1, 'max' => 6, 'null' => TRUE, 'cast' => 'int'],
			'planningDelayedMax' => ['int8', 'min' => 1, 'max' => 6, 'null' => TRUE, 'cast' => 'int'],
			'featureTime' => ['bool', 'cast' => 'bool'],
			'featureStock' => ['bool', 'cast' => 'bool'],
			'stockNotes' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'stockNotesUpdatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'stockNotesUpdatedBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'hasCampaign' => ['bool', 'cast' => 'bool'],
			'hasShops' => ['bool', 'cast' => 'bool'],
			'hasSales' => ['bool', 'cast' => 'bool'],
			'hasCultivations' => ['bool', 'cast' => 'bool'],
			'startedAt' => ['int16', 'min' => date('Y') - 100, 'max' => date('Y') + 10, 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\farm\Farm::ACTIVE, \farm\Farm::CLOSED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'legalName', 'legalEmail', 'siret', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity', 'vignette', 'place', 'placeLngLat', 'url', 'description', 'logo', 'emailBanner', 'emailFooter', 'emailDefaultTime', 'seasonFirst', 'seasonLast', 'rotationYears', 'rotationExclude', 'quality', 'defaultBedLength', 'defaultBedWidth', 'defaultAlleyWidth', 'calendarMonthStart', 'calendarMonthStop', 'planningDelayedMax', 'featureTime', 'featureStock', 'stockNotes', 'stockNotesUpdatedAt', 'stockNotesUpdatedBy', 'hasCampaign', 'hasShops', 'hasSales', 'hasCultivations', 'startedAt', 'createdAt', 'status'
		]);

		$this->propertiesToModule += [
			'stockNotesUpdatedBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'rotationYears' :
				return 4;

			case 'rotationExclude' :
				return [];

			case 'quality' :
				return Farm::ORGANIC;

			case 'calendarMonthStart' :
				return 10;

			case 'calendarMonthStop' :
				return 3;

			case 'planningDelayedMax' :
				return 2;

			case 'featureTime' :
				return TRUE;

			case 'featureStock' :
				return FALSE;

			case 'hasCampaign' :
				return FALSE;

			case 'hasShops' :
				return FALSE;

			case 'hasSales' :
				return FALSE;

			case 'hasCultivations' :
				return FALSE;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Farm::ACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'placeLngLat' :
				return $value === NULL ? NULL : new \Sql($this->pdo()->api->getPoint($value));

			case 'rotationExclude' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'quality' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'placeLngLat' :
				return $value === NULL ? NULL : json_encode(json_decode($value, TRUE)['coordinates']);

			case 'rotationExclude' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): FarmModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FarmModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): FarmModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): FarmModel {
		return $this->where('name', ...$data);
	}

	public function whereLegalName(...$data): FarmModel {
		return $this->where('legalName', ...$data);
	}

	public function whereLegalEmail(...$data): FarmModel {
		return $this->where('legalEmail', ...$data);
	}

	public function whereSiret(...$data): FarmModel {
		return $this->where('siret', ...$data);
	}

	public function whereLegalStreet1(...$data): FarmModel {
		return $this->where('legalStreet1', ...$data);
	}

	public function whereLegalStreet2(...$data): FarmModel {
		return $this->where('legalStreet2', ...$data);
	}

	public function whereLegalPostcode(...$data): FarmModel {
		return $this->where('legalPostcode', ...$data);
	}

	public function whereLegalCity(...$data): FarmModel {
		return $this->where('legalCity', ...$data);
	}

	public function whereVignette(...$data): FarmModel {
		return $this->where('vignette', ...$data);
	}

	public function wherePlace(...$data): FarmModel {
		return $this->where('place', ...$data);
	}

	public function wherePlaceLngLat(...$data): FarmModel {
		return $this->where('placeLngLat', ...$data);
	}

	public function whereUrl(...$data): FarmModel {
		return $this->where('url', ...$data);
	}

	public function whereDescription(...$data): FarmModel {
		return $this->where('description', ...$data);
	}

	public function whereLogo(...$data): FarmModel {
		return $this->where('logo', ...$data);
	}

	public function whereEmailBanner(...$data): FarmModel {
		return $this->where('emailBanner', ...$data);
	}

	public function whereEmailFooter(...$data): FarmModel {
		return $this->where('emailFooter', ...$data);
	}

	public function whereEmailDefaultTime(...$data): FarmModel {
		return $this->where('emailDefaultTime', ...$data);
	}

	public function whereSeasonFirst(...$data): FarmModel {
		return $this->where('seasonFirst', ...$data);
	}

	public function whereSeasonLast(...$data): FarmModel {
		return $this->where('seasonLast', ...$data);
	}

	public function whereRotationYears(...$data): FarmModel {
		return $this->where('rotationYears', ...$data);
	}

	public function whereRotationExclude(...$data): FarmModel {
		return $this->where('rotationExclude', ...$data);
	}

	public function whereQuality(...$data): FarmModel {
		return $this->where('quality', ...$data);
	}

	public function whereDefaultBedLength(...$data): FarmModel {
		return $this->where('defaultBedLength', ...$data);
	}

	public function whereDefaultBedWidth(...$data): FarmModel {
		return $this->where('defaultBedWidth', ...$data);
	}

	public function whereDefaultAlleyWidth(...$data): FarmModel {
		return $this->where('defaultAlleyWidth', ...$data);
	}

	public function whereCalendarMonthStart(...$data): FarmModel {
		return $this->where('calendarMonthStart', ...$data);
	}

	public function whereCalendarMonthStop(...$data): FarmModel {
		return $this->where('calendarMonthStop', ...$data);
	}

	public function wherePlanningDelayedMax(...$data): FarmModel {
		return $this->where('planningDelayedMax', ...$data);
	}

	public function whereFeatureTime(...$data): FarmModel {
		return $this->where('featureTime', ...$data);
	}

	public function whereFeatureStock(...$data): FarmModel {
		return $this->where('featureStock', ...$data);
	}

	public function whereStockNotes(...$data): FarmModel {
		return $this->where('stockNotes', ...$data);
	}

	public function whereStockNotesUpdatedAt(...$data): FarmModel {
		return $this->where('stockNotesUpdatedAt', ...$data);
	}

	public function whereStockNotesUpdatedBy(...$data): FarmModel {
		return $this->where('stockNotesUpdatedBy', ...$data);
	}

	public function whereHasCampaign(...$data): FarmModel {
		return $this->where('hasCampaign', ...$data);
	}

	public function whereHasShops(...$data): FarmModel {
		return $this->where('hasShops', ...$data);
	}

	public function whereHasSales(...$data): FarmModel {
		return $this->where('hasSales', ...$data);
	}

	public function whereHasCultivations(...$data): FarmModel {
		return $this->where('hasCultivations', ...$data);
	}

	public function whereStartedAt(...$data): FarmModel {
		return $this->where('startedAt', ...$data);
	}

	public function whereCreatedAt(...$data): FarmModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): FarmModel {
		return $this->where('status', ...$data);
	}


}


abstract class FarmCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Farm {

		$e = new Farm();

		if(empty($id)) {
			Farm::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Farm::getSelection();
		}

		if(Farm::model()
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
			$properties = Farm::getSelection();
		}

		if($sort !== NULL) {
			Farm::model()->sort($sort);
		}

		return Farm::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Farm {

		return new Farm(['id' => NULL]);

	}

	public static function create(Farm $e): void {

		Farm::model()->insert($e);

	}

	public static function update(Farm $e, array $properties): void {

		$e->expects(['id']);

		Farm::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Farm $e, array $properties): void {

		Farm::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Farm $e): void {

		$e->expects(['id']);

		Farm::model()->delete($e);

	}

}


class FarmPage extends \ModulePage {

	protected string $module = 'farm\Farm';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FarmLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FarmLib::getPropertiesUpdate()
		);
	}

}
?>