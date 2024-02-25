<?php
namespace map;

abstract class ZoneElement extends \Element {

	use \FilterElement;

	private static ?ZoneModel $model = NULL;

	public static function getSelection(): array {
		return Zone::model()->getProperties();
	}

	public static function model(): ZoneModel {
		if(self::$model === NULL) {
			self::$model = new ZoneModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Zone::'.$failName, $arguments, $wrapper);
	}

}


class ZoneModel extends \ModuleModel {

	protected string $module = 'map\Zone';
	protected string $package = 'map';
	protected string $table = 'mapZone';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'area' => ['int32', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'coordinates' => ['polygon', 'null' => TRUE, 'cast' => 'json'],
			'seasonFirst' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'seasonLast' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'updatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'farm', 'area', 'coordinates', 'seasonFirst', 'seasonLast', 'createdAt', 'createdBy', 'updatedAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

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

	public function select(...$fields): ZoneModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ZoneModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ZoneModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): ZoneModel {
		return $this->where('name', ...$data);
	}

	public function whereFarm(...$data): ZoneModel {
		return $this->where('farm', ...$data);
	}

	public function whereArea(...$data): ZoneModel {
		return $this->where('area', ...$data);
	}

	public function whereCoordinates(...$data): ZoneModel {
		return $this->where('coordinates', ...$data);
	}

	public function whereSeasonFirst(...$data): ZoneModel {
		return $this->where('seasonFirst', ...$data);
	}

	public function whereSeasonLast(...$data): ZoneModel {
		return $this->where('seasonLast', ...$data);
	}

	public function whereCreatedAt(...$data): ZoneModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): ZoneModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereUpdatedAt(...$data): ZoneModel {
		return $this->where('updatedAt', ...$data);
	}


}


abstract class ZoneCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Zone {

		$e = new Zone();

		if(empty($id)) {
			Zone::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Zone::getSelection();
		}

		if(Zone::model()
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
			$properties = Zone::getSelection();
		}

		if($sort !== NULL) {
			Zone::model()->sort($sort);
		}

		return Zone::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Zone {

		return new Zone(['id' => NULL]);

	}

	public static function create(Zone $e): void {

		Zone::model()->insert($e);

	}

	public static function update(Zone $e, array $properties): void {

		$e->expects(['id']);

		Zone::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Zone $e, array $properties): void {

		Zone::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Zone $e): void {

		$e->expects(['id']);

		Zone::model()->delete($e);

	}

}


class ZonePage extends \ModulePage {

	protected string $module = 'map\Zone';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ZoneLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ZoneLib::getPropertiesUpdate()
		);
	}

}
?>