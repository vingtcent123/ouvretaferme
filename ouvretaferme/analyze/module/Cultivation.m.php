<?php
namespace analyze;

abstract class CultivationElement extends \Element {

	use \FilterElement;

	private static ?CultivationModel $model = NULL;

	public static function getSelection(): array {
		return Cultivation::model()->getProperties();
	}

	public static function model(): CultivationModel {
		if(self::$model === NULL) {
			self::$model = new CultivationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Cultivation::'.$failName, $arguments, $wrapper);
	}

}


class CultivationModel extends \ModuleModel {

	protected string $module = 'analyze\Cultivation';
	protected string $package = 'analyze';
	protected string $table = 'analyzeCultivation';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'report' => ['element32', 'analyze\Report', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'series' => ['element32', 'series\Series', 'cast' => 'element'],
			'cultivation' => ['element32', 'series\Cultivation', 'cast' => 'element'],
			'harvestedByUnit' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'turnoverByUnit' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'area' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'workingTime' => ['float32', 'min' => 0.0, 'max' => NULL, 'cast' => 'float'],
			'costs' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'turnover' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'report', 'farm', 'series', 'cultivation', 'harvestedByUnit', 'turnoverByUnit', 'area', 'workingTime', 'costs', 'turnover'
		]);

		$this->propertiesToModule += [
			'report' => 'analyze\Report',
			'farm' => 'farm\Farm',
			'series' => 'series\Series',
			'cultivation' => 'series\Cultivation',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['report']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'area' :
				return 0;

			case 'workingTime' :
				return 0;

			case 'costs' :
				return 0;

			case 'turnover' :
				return 0;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'harvestedByUnit' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'turnoverByUnit' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'harvestedByUnit' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'turnoverByUnit' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): CultivationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CultivationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CultivationModel {
		return $this->where('id', ...$data);
	}

	public function whereReport(...$data): CultivationModel {
		return $this->where('report', ...$data);
	}

	public function whereFarm(...$data): CultivationModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeries(...$data): CultivationModel {
		return $this->where('series', ...$data);
	}

	public function whereCultivation(...$data): CultivationModel {
		return $this->where('cultivation', ...$data);
	}

	public function whereHarvestedByUnit(...$data): CultivationModel {
		return $this->where('harvestedByUnit', ...$data);
	}

	public function whereTurnoverByUnit(...$data): CultivationModel {
		return $this->where('turnoverByUnit', ...$data);
	}

	public function whereArea(...$data): CultivationModel {
		return $this->where('area', ...$data);
	}

	public function whereWorkingTime(...$data): CultivationModel {
		return $this->where('workingTime', ...$data);
	}

	public function whereCosts(...$data): CultivationModel {
		return $this->where('costs', ...$data);
	}

	public function whereTurnover(...$data): CultivationModel {
		return $this->where('turnover', ...$data);
	}


}


abstract class CultivationCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Cultivation {

		$e = new Cultivation();

		if(empty($id)) {
			Cultivation::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Cultivation::getSelection();
		}

		if(Cultivation::model()
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
			$properties = Cultivation::getSelection();
		}

		if($sort !== NULL) {
			Cultivation::model()->sort($sort);
		}

		return Cultivation::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Cultivation {

		return new Cultivation(['id' => NULL]);

	}

	public static function create(Cultivation $e): void {

		Cultivation::model()->insert($e);

	}

	public static function update(Cultivation $e, array $properties): void {

		$e->expects(['id']);

		Cultivation::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Cultivation $e, array $properties): void {

		Cultivation::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Cultivation $e): void {

		$e->expects(['id']);

		Cultivation::model()->delete($e);

	}

}


class CultivationPage extends \ModulePage {

	protected string $module = 'analyze\Cultivation';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CultivationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CultivationLib::getPropertiesUpdate()
		);
	}

}
?>