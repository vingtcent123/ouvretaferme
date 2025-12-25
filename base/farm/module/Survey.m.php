<?php
namespace farm;

abstract class SurveyElement extends \Element {

	use \FilterElement;

	private static ?SurveyModel $model = NULL;

	const CERFRANCE = 'cerfrance';
	const AFOCG = 'afocg';
	const AUTONOME = 'autonome';
	const OTHER = 'other';
	const NONE = 'none';

	const REEL = 'reel';
	const MICROBA = 'microba';

	public static function getSelection(): array {
		return Survey::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): SurveyModel {
		if(self::$model === NULL) {
			self::$model = new SurveyModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Survey::'.$failName, $arguments, $wrapper);
	}

}


class SurveyModel extends \ModuleModel {

	protected string $module = 'farm\Survey';
	protected string $package = 'farm';
	protected string $table = 'farmSurvey';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'unique' => TRUE, 'cast' => 'element'],
			'createdAt' => ['date', 'cast' => 'string'],
			'number' => ['int32', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'why' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'feedback' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'formation' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'productionFeature' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'productionResearch' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'sellingFeature' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'accounting' => ['enum', [\farm\Survey::CERFRANCE, \farm\Survey::AFOCG, \farm\Survey::AUTONOME, \farm\Survey::OTHER, \farm\Survey::NONE], 'cast' => 'enum'],
			'accountingType' => ['enum', [\farm\Survey::REEL, \farm\Survey::MICROBA, \farm\Survey::OTHER], 'cast' => 'enum'],
			'accountingAutonomy' => ['bool', 'cast' => 'bool'],
			'accountingOtf' => ['bool', 'cast' => 'bool'],
			'accountingInfo' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'coopTroc' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'coopMercuriale' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'coopItk' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'coopOther' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'coopCommandes' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'other' => ['text16', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'createdAt', 'number', 'why', 'feedback', 'formation', 'productionFeature', 'productionResearch', 'sellingFeature', 'accounting', 'accountingType', 'accountingAutonomy', 'accountingOtf', 'accountingInfo', 'coopTroc', 'coopMercuriale', 'coopItk', 'coopOther', 'coopCommandes', 'other'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('CURDATE()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'accounting' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'accountingType' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): SurveyModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SurveyModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SurveyModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): SurveyModel {
		return $this->where('farm', ...$data);
	}

	public function whereCreatedAt(...$data): SurveyModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereNumber(...$data): SurveyModel {
		return $this->where('number', ...$data);
	}

	public function whereWhy(...$data): SurveyModel {
		return $this->where('why', ...$data);
	}

	public function whereFeedback(...$data): SurveyModel {
		return $this->where('feedback', ...$data);
	}

	public function whereFormation(...$data): SurveyModel {
		return $this->where('formation', ...$data);
	}

	public function whereProductionFeature(...$data): SurveyModel {
		return $this->where('productionFeature', ...$data);
	}

	public function whereProductionResearch(...$data): SurveyModel {
		return $this->where('productionResearch', ...$data);
	}

	public function whereSellingFeature(...$data): SurveyModel {
		return $this->where('sellingFeature', ...$data);
	}

	public function whereAccounting(...$data): SurveyModel {
		return $this->where('accounting', ...$data);
	}

	public function whereAccountingType(...$data): SurveyModel {
		return $this->where('accountingType', ...$data);
	}

	public function whereAccountingAutonomy(...$data): SurveyModel {
		return $this->where('accountingAutonomy', ...$data);
	}

	public function whereAccountingOtf(...$data): SurveyModel {
		return $this->where('accountingOtf', ...$data);
	}

	public function whereAccountingInfo(...$data): SurveyModel {
		return $this->where('accountingInfo', ...$data);
	}

	public function whereCoopTroc(...$data): SurveyModel {
		return $this->where('coopTroc', ...$data);
	}

	public function whereCoopMercuriale(...$data): SurveyModel {
		return $this->where('coopMercuriale', ...$data);
	}

	public function whereCoopItk(...$data): SurveyModel {
		return $this->where('coopItk', ...$data);
	}

	public function whereCoopOther(...$data): SurveyModel {
		return $this->where('coopOther', ...$data);
	}

	public function whereCoopCommandes(...$data): SurveyModel {
		return $this->where('coopCommandes', ...$data);
	}

	public function whereOther(...$data): SurveyModel {
		return $this->where('other', ...$data);
	}


}


abstract class SurveyCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Survey {

		$e = new Survey();

		if(empty($id)) {
			Survey::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Survey::getSelection();
		}

		if(Survey::model()
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
			$properties = Survey::getSelection();
		}

		if($sort !== NULL) {
			Survey::model()->sort($sort);
		}

		return Survey::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Survey {

		return new Survey(['id' => NULL]);

	}

	public static function create(Survey $e): void {

		Survey::model()->insert($e);

	}

	public static function update(Survey $e, array $properties): void {

		$e->expects(['id']);

		Survey::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Survey $e, array $properties): void {

		Survey::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Survey $e): void {

		$e->expects(['id']);

		Survey::model()->delete($e);

	}

}


class SurveyPage extends \ModulePage {

	protected string $module = 'farm\Survey';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SurveyLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SurveyLib::getPropertiesUpdate()
		);
	}

}
?>