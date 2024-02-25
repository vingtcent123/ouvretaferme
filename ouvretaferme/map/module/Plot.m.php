<?php
namespace map;

abstract class PlotElement extends \Element {

	use \FilterElement;

	private static ?PlotModel $model = NULL;

	const GREENHOUSE = 'greenhouse';
	const OUTDOOR = 'outdoor';

	public static function getSelection(): array {
		return Plot::model()->getProperties();
	}

	public static function model(): PlotModel {
		if(self::$model === NULL) {
			self::$model = new PlotModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Plot::'.$failName, $arguments, $wrapper);
	}

}


class PlotModel extends \ModuleModel {

	protected string $module = 'map\Plot';
	protected string $package = 'map';
	protected string $table = 'mapPlot';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'zone' => ['element32', 'map\Zone', 'cast' => 'element'],
			'zoneFill' => ['bool', 'cast' => 'bool'],
			'mode' => ['enum', [\map\Plot::GREENHOUSE, \map\Plot::OUTDOOR], 'cast' => 'enum'],
			'area' => ['int32', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'coordinates' => ['polygon', 'null' => TRUE, 'cast' => 'json'],
			'seasonFirst' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'seasonLast' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'updatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'farm', 'zone', 'zoneFill', 'mode', 'area', 'coordinates', 'seasonFirst', 'seasonLast', 'createdAt', 'createdBy', 'updatedAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'zone' => 'map\Zone',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['zone']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'zoneFill' :
				return FALSE;

			case 'mode' :
				return Plot::OUTDOOR;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'mode' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'coordinates' :
				return $value === NULL ? NULL : new \Sql($this->pdo()->api->getPolygon($value));

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'coordinates' :
				return $value === NULL ? NULL : json_encode(json_decode($value, TRUE)['coordinates'][0]);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): PlotModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PlotModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PlotModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): PlotModel {
		return $this->where('name', ...$data);
	}

	public function whereFarm(...$data): PlotModel {
		return $this->where('farm', ...$data);
	}

	public function whereZone(...$data): PlotModel {
		return $this->where('zone', ...$data);
	}

	public function whereZoneFill(...$data): PlotModel {
		return $this->where('zoneFill', ...$data);
	}

	public function whereMode(...$data): PlotModel {
		return $this->where('mode', ...$data);
	}

	public function whereArea(...$data): PlotModel {
		return $this->where('area', ...$data);
	}

	public function whereCoordinates(...$data): PlotModel {
		return $this->where('coordinates', ...$data);
	}

	public function whereSeasonFirst(...$data): PlotModel {
		return $this->where('seasonFirst', ...$data);
	}

	public function whereSeasonLast(...$data): PlotModel {
		return $this->where('seasonLast', ...$data);
	}

	public function whereCreatedAt(...$data): PlotModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): PlotModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereUpdatedAt(...$data): PlotModel {
		return $this->where('updatedAt', ...$data);
	}


}


abstract class PlotCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Plot {

		$e = new Plot();

		if(empty($id)) {
			Plot::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Plot::getSelection();
		}

		if(Plot::model()
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
			$properties = Plot::getSelection();
		}

		if($sort !== NULL) {
			Plot::model()->sort($sort);
		}

		return Plot::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Plot {

		return new Plot(['id' => NULL]);

	}

	public static function create(Plot $e): void {

		Plot::model()->insert($e);

	}

	public static function update(Plot $e, array $properties): void {

		$e->expects(['id']);

		Plot::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Plot $e, array $properties): void {

		Plot::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Plot $e): void {

		$e->expects(['id']);

		Plot::model()->delete($e);

	}

}


class PlotPage extends \ModulePage {

	protected string $module = 'map\Plot';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PlotLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PlotLib::getPropertiesUpdate()
		);
	}

}
?>