<?php
namespace series;

abstract class PlaceElement extends \Element {

	use \FilterElement;

	private static ?PlaceModel $model = NULL;

	public static function getSelection(): array {
		return Place::model()->getProperties();
	}

	public static function model(): PlaceModel {
		if(self::$model === NULL) {
			self::$model = new PlaceModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Place::'.$failName, $arguments, $wrapper);
	}

}


class PlaceModel extends \ModuleModel {

	protected string $module = 'series\Place';
	protected string $package = 'series';
	protected string $table = 'seriesPlace';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'series' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'task' => ['element32', 'series\Task', 'null' => TRUE, 'cast' => 'element'],
			'zone' => ['element32', 'map\Zone', 'cast' => 'element'],
			'plot' => ['element32', 'map\Plot', 'cast' => 'element'],
			'bed' => ['element32', 'map\Bed', 'cast' => 'element'],
			'length' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'width' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'area' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'season', 'series', 'task', 'zone', 'plot', 'bed', 'length', 'width', 'area', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'series' => 'series\Series',
			'task' => 'series\Task',
			'zone' => 'map\Zone',
			'plot' => 'map\Plot',
			'bed' => 'map\Bed',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'season'],
			['bed'],
			['zone'],
			['plot']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['series', 'bed'],
			['task', 'bed']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'area' :
				return new \Sql('IF(length IS NOT NULL AND width IS NOT NULL, length * width / 100, NULL)');

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): PlaceModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PlaceModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PlaceModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): PlaceModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeason(...$data): PlaceModel {
		return $this->where('season', ...$data);
	}

	public function whereSeries(...$data): PlaceModel {
		return $this->where('series', ...$data);
	}

	public function whereTask(...$data): PlaceModel {
		return $this->where('task', ...$data);
	}

	public function whereZone(...$data): PlaceModel {
		return $this->where('zone', ...$data);
	}

	public function wherePlot(...$data): PlaceModel {
		return $this->where('plot', ...$data);
	}

	public function whereBed(...$data): PlaceModel {
		return $this->where('bed', ...$data);
	}

	public function whereLength(...$data): PlaceModel {
		return $this->where('length', ...$data);
	}

	public function whereWidth(...$data): PlaceModel {
		return $this->where('width', ...$data);
	}

	public function whereArea(...$data): PlaceModel {
		return $this->where('area', ...$data);
	}

	public function whereCreatedAt(...$data): PlaceModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): PlaceModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class PlaceCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Place {

		$e = new Place();

		if(empty($id)) {
			Place::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Place::getSelection();
		}

		if(Place::model()
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
			$properties = Place::getSelection();
		}

		if($sort !== NULL) {
			Place::model()->sort($sort);
		}

		return Place::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Place {

		return new Place(['id' => NULL]);

	}

	public static function create(Place $e): void {

		Place::model()->insert($e);

	}

	public static function update(Place $e, array $properties): void {

		$e->expects(['id']);

		Place::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Place $e, array $properties): void {

		Place::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Place $e): void {

		$e->expects(['id']);

		Place::model()->delete($e);

	}

}


class PlacePage extends \ModulePage {

	protected string $module = 'series\Place';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PlaceLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PlaceLib::getPropertiesUpdate()
		);
	}

}
?>