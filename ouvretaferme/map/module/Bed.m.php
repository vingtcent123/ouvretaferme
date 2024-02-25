<?php
namespace map;

abstract class BedElement extends \Element {

	use \FilterElement;

	private static ?BedModel $model = NULL;

	public static function getSelection(): array {
		return Bed::model()->getProperties();
	}

	public static function model(): BedModel {
		if(self::$model === NULL) {
			self::$model = new BedModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Bed::'.$failName, $arguments, $wrapper);
	}

}


class BedModel extends \ModuleModel {

	protected string $module = 'map\Bed';
	protected string $package = 'map';
	protected string $table = 'mapBed';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 20, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'zone' => ['element32', 'map\Zone', 'cast' => 'element'],
			'zoneFill' => ['bool', 'cast' => 'bool'],
			'plot' => ['element32', 'map\Plot', 'cast' => 'element'],
			'plotFill' => ['bool', 'cast' => 'bool'],
			'length' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'width' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'area' => ['float32', 'min' => 0.01, 'max' => NULL, 'cast' => 'float'],
			'greenhouse' => ['element32', 'map\Greenhouse', 'null' => TRUE, 'cast' => 'element'],
			'seasonFirst' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'seasonLast' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'updatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'farm', 'zone', 'zoneFill', 'plot', 'plotFill', 'length', 'width', 'area', 'greenhouse', 'seasonFirst', 'seasonLast', 'createdAt', 'createdBy', 'updatedAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'zone' => 'map\Zone',
			'plot' => 'map\Plot',
			'greenhouse' => 'map\Greenhouse',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['zone'],
			['plot'],
			['greenhouse']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['plot', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'zoneFill' :
				return FALSE;

			case 'plotFill' :
				return FALSE;

			case 'area' :
				return new \Sql('length * width / 100');

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): BedModel {
		return parent::select(...$fields);
	}

	public function where(...$data): BedModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): BedModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): BedModel {
		return $this->where('name', ...$data);
	}

	public function whereFarm(...$data): BedModel {
		return $this->where('farm', ...$data);
	}

	public function whereZone(...$data): BedModel {
		return $this->where('zone', ...$data);
	}

	public function whereZoneFill(...$data): BedModel {
		return $this->where('zoneFill', ...$data);
	}

	public function wherePlot(...$data): BedModel {
		return $this->where('plot', ...$data);
	}

	public function wherePlotFill(...$data): BedModel {
		return $this->where('plotFill', ...$data);
	}

	public function whereLength(...$data): BedModel {
		return $this->where('length', ...$data);
	}

	public function whereWidth(...$data): BedModel {
		return $this->where('width', ...$data);
	}

	public function whereArea(...$data): BedModel {
		return $this->where('area', ...$data);
	}

	public function whereGreenhouse(...$data): BedModel {
		return $this->where('greenhouse', ...$data);
	}

	public function whereSeasonFirst(...$data): BedModel {
		return $this->where('seasonFirst', ...$data);
	}

	public function whereSeasonLast(...$data): BedModel {
		return $this->where('seasonLast', ...$data);
	}

	public function whereCreatedAt(...$data): BedModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): BedModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereUpdatedAt(...$data): BedModel {
		return $this->where('updatedAt', ...$data);
	}


}


abstract class BedCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Bed {

		$e = new Bed();

		if(empty($id)) {
			Bed::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Bed::getSelection();
		}

		if(Bed::model()
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
			$properties = Bed::getSelection();
		}

		if($sort !== NULL) {
			Bed::model()->sort($sort);
		}

		return Bed::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Bed {

		return new Bed(['id' => NULL]);

	}

	public static function create(Bed $e): void {

		Bed::model()->insert($e);

	}

	public static function update(Bed $e, array $properties): void {

		$e->expects(['id']);

		Bed::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Bed $e, array $properties): void {

		Bed::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Bed $e): void {

		$e->expects(['id']);

		Bed::model()->delete($e);

	}

}


class BedPage extends \ModulePage {

	protected string $module = 'map\Bed';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? BedLib::getPropertiesCreate(),
		   $propertiesUpdate ?? BedLib::getPropertiesUpdate()
		);
	}

}
?>