<?php
namespace plant;

abstract class ForecastElement extends \Element {

	use \FilterElement;

	private static ?ForecastModel $model = NULL;

	const KG = 'kg';
	const UNIT = 'unit';
	const BUNCH = 'bunch';

	public static function getSelection(): array {
		return Forecast::model()->getProperties();
	}

	public static function model(): ForecastModel {
		if(self::$model === NULL) {
			self::$model = new ForecastModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Forecast::'.$failName, $arguments, $wrapper);
	}

}


class ForecastModel extends \ModuleModel {

	protected string $module = 'plant\Forecast';
	protected string $package = 'plant';
	protected string $table = 'plantForecast';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'plant' => ['element32', 'plant\Plant', 'cast' => 'element'],
			'unit' => ['enum', [\plant\Forecast::KG, \plant\Forecast::UNIT, \plant\Forecast::BUNCH], 'cast' => 'enum'],
			'harvestObjective' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'proPart' => ['int8', 'min' => 0, 'max' => 100, 'cast' => 'int'],
			'proPrice' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'privatePart' => ['int8', 'min' => 0, 'max' => 100, 'cast' => 'int'],
			'privatePrice' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'season', 'plant', 'unit', 'harvestObjective', 'proPart', 'proPrice', 'privatePart', 'privatePrice'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'plant' => 'plant\Plant',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['plant']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'season', 'plant', 'unit']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'proPart' :
				return 0;

			case 'privatePart' :
				return 100;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'unit' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): ForecastModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ForecastModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ForecastModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): ForecastModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeason(...$data): ForecastModel {
		return $this->where('season', ...$data);
	}

	public function wherePlant(...$data): ForecastModel {
		return $this->where('plant', ...$data);
	}

	public function whereUnit(...$data): ForecastModel {
		return $this->where('unit', ...$data);
	}

	public function whereHarvestObjective(...$data): ForecastModel {
		return $this->where('harvestObjective', ...$data);
	}

	public function whereProPart(...$data): ForecastModel {
		return $this->where('proPart', ...$data);
	}

	public function whereProPrice(...$data): ForecastModel {
		return $this->where('proPrice', ...$data);
	}

	public function wherePrivatePart(...$data): ForecastModel {
		return $this->where('privatePart', ...$data);
	}

	public function wherePrivatePrice(...$data): ForecastModel {
		return $this->where('privatePrice', ...$data);
	}


}


abstract class ForecastCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Forecast {

		$e = new Forecast();

		if(empty($id)) {
			Forecast::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Forecast::getSelection();
		}

		if(Forecast::model()
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
			$properties = Forecast::getSelection();
		}

		if($sort !== NULL) {
			Forecast::model()->sort($sort);
		}

		return Forecast::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Forecast {

		return new Forecast(['id' => NULL]);

	}

	public static function create(Forecast $e): void {

		Forecast::model()->insert($e);

	}

	public static function update(Forecast $e, array $properties): void {

		$e->expects(['id']);

		Forecast::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Forecast $e, array $properties): void {

		Forecast::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Forecast $e): void {

		$e->expects(['id']);

		Forecast::model()->delete($e);

	}

}


class ForecastPage extends \ModulePage {

	protected string $module = 'plant\Forecast';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ForecastLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ForecastLib::getPropertiesUpdate()
		);
	}

}
?>