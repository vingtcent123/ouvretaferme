<?php
namespace analyze;

abstract class ReportElement extends \Element {

	use \FilterElement;

	private static ?ReportModel $model = NULL;

	const RELATIVE = 'relative';
	const ABSOLUTE = 'absolute';

	public static function getSelection(): array {
		return Report::model()->getProperties();
	}

	public static function model(): ReportModel {
		if(self::$model === NULL) {
			self::$model = new ReportModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Report::'.$failName, $arguments, $wrapper);
	}

}


class ReportModel extends \ModuleModel {

	protected string $module = 'analyze\Report';
	protected string $package = 'analyze';
	protected string $table = 'analyzeReport';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'description' => ['editor16', 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'plant' => ['element32', 'plant\Plant', 'cast' => 'element'],
			'area' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'workingTime' => ['float32', 'min' => 0.0, 'max' => NULL, 'cast' => 'float'],
			'costs' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'turnover' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'testArea' => ['int24', 'null' => TRUE, 'cast' => 'int'],
			'testAreaOperator' => ['enum', [\analyze\Report::RELATIVE, \analyze\Report::ABSOLUTE], 'null' => TRUE, 'cast' => 'enum'],
			'testWorkingTime' => ['int24', 'null' => TRUE, 'cast' => 'int'],
			'testWorkingTimeOperator' => ['enum', [\analyze\Report::RELATIVE, \analyze\Report::ABSOLUTE], 'null' => TRUE, 'cast' => 'enum'],
			'testCosts' => ['int24', 'null' => TRUE, 'cast' => 'int'],
			'testCostsOperator' => ['enum', [\analyze\Report::RELATIVE, \analyze\Report::ABSOLUTE], 'null' => TRUE, 'cast' => 'enum'],
			'testTurnover' => ['int24', 'null' => TRUE, 'cast' => 'int'],
			'testTurnoverOperator' => ['enum', [\analyze\Report::RELATIVE, \analyze\Report::ABSOLUTE], 'null' => TRUE, 'cast' => 'enum'],
			'firstSaleAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'lastSaleAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'description', 'farm', 'season', 'plant', 'area', 'workingTime', 'costs', 'turnover', 'testArea', 'testAreaOperator', 'testWorkingTime', 'testWorkingTimeOperator', 'testCosts', 'testCostsOperator', 'testTurnover', 'testTurnoverOperator', 'firstSaleAt', 'lastSaleAt', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'plant' => 'plant\Plant',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['plant', 'season', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'testAreaOperator' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'testWorkingTimeOperator' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'testCostsOperator' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'testTurnoverOperator' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): ReportModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ReportModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ReportModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): ReportModel {
		return $this->where('name', ...$data);
	}

	public function whereDescription(...$data): ReportModel {
		return $this->where('description', ...$data);
	}

	public function whereFarm(...$data): ReportModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeason(...$data): ReportModel {
		return $this->where('season', ...$data);
	}

	public function wherePlant(...$data): ReportModel {
		return $this->where('plant', ...$data);
	}

	public function whereArea(...$data): ReportModel {
		return $this->where('area', ...$data);
	}

	public function whereWorkingTime(...$data): ReportModel {
		return $this->where('workingTime', ...$data);
	}

	public function whereCosts(...$data): ReportModel {
		return $this->where('costs', ...$data);
	}

	public function whereTurnover(...$data): ReportModel {
		return $this->where('turnover', ...$data);
	}

	public function whereTestArea(...$data): ReportModel {
		return $this->where('testArea', ...$data);
	}

	public function whereTestAreaOperator(...$data): ReportModel {
		return $this->where('testAreaOperator', ...$data);
	}

	public function whereTestWorkingTime(...$data): ReportModel {
		return $this->where('testWorkingTime', ...$data);
	}

	public function whereTestWorkingTimeOperator(...$data): ReportModel {
		return $this->where('testWorkingTimeOperator', ...$data);
	}

	public function whereTestCosts(...$data): ReportModel {
		return $this->where('testCosts', ...$data);
	}

	public function whereTestCostsOperator(...$data): ReportModel {
		return $this->where('testCostsOperator', ...$data);
	}

	public function whereTestTurnover(...$data): ReportModel {
		return $this->where('testTurnover', ...$data);
	}

	public function whereTestTurnoverOperator(...$data): ReportModel {
		return $this->where('testTurnoverOperator', ...$data);
	}

	public function whereFirstSaleAt(...$data): ReportModel {
		return $this->where('firstSaleAt', ...$data);
	}

	public function whereLastSaleAt(...$data): ReportModel {
		return $this->where('lastSaleAt', ...$data);
	}

	public function whereCreatedAt(...$data): ReportModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class ReportCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Report {

		$e = new Report();

		if(empty($id)) {
			Report::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Report::getSelection();
		}

		if(Report::model()
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
			$properties = Report::getSelection();
		}

		if($sort !== NULL) {
			Report::model()->sort($sort);
		}

		return Report::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Report {

		return new Report(['id' => NULL]);

	}

	public static function create(Report $e): void {

		Report::model()->insert($e);

	}

	public static function update(Report $e, array $properties): void {

		$e->expects(['id']);

		Report::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Report $e, array $properties): void {

		Report::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Report $e): void {

		$e->expects(['id']);

		Report::model()->delete($e);

	}

}


class ReportPage extends \ModulePage {

	protected string $module = 'analyze\Report';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ReportLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ReportLib::getPropertiesUpdate()
		);
	}

}
?>