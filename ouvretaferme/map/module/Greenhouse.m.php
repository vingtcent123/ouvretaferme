<?php
namespace map;

abstract class GreenhouseElement extends \Element {

	use \FilterElement;

	private static ?GreenhouseModel $model = NULL;

	public static function getSelection(): array {
		return Greenhouse::model()->getProperties();
	}

	public static function model(): GreenhouseModel {
		if(self::$model === NULL) {
			self::$model = new GreenhouseModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Greenhouse::'.$failName, $arguments, $wrapper);
	}

}


class GreenhouseModel extends \ModuleModel {

	protected string $module = 'map\Greenhouse';
	protected string $package = 'map';
	protected string $table = 'mapGreenhouse';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'zone' => ['element32', 'map\Zone', 'cast' => 'element'],
			'zoneFill' => ['bool', 'cast' => 'bool'],
			'plot' => ['element32', 'map\Plot', 'cast' => 'element'],
			'length' => ['float32', 'min' => 1, 'max' => NULL, 'cast' => 'float'],
			'width' => ['float32', 'min' => 1, 'max' => NULL, 'cast' => 'float'],
			'area' => ['float32', 'min' => 1, 'max' => NULL, 'cast' => 'float'],
			'seasonFirst' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'seasonLast' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'farm', 'zone', 'zoneFill', 'plot', 'length', 'width', 'area', 'seasonFirst', 'seasonLast', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'zone' => 'map\Zone',
			'plot' => 'map\Plot',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['zone'],
			['plot']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'area' :
				return new \Sql('length * width');

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): GreenhouseModel {
		return parent::select(...$fields);
	}

	public function where(...$data): GreenhouseModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): GreenhouseModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): GreenhouseModel {
		return $this->where('name', ...$data);
	}

	public function whereFarm(...$data): GreenhouseModel {
		return $this->where('farm', ...$data);
	}

	public function whereZone(...$data): GreenhouseModel {
		return $this->where('zone', ...$data);
	}

	public function whereZoneFill(...$data): GreenhouseModel {
		return $this->where('zoneFill', ...$data);
	}

	public function wherePlot(...$data): GreenhouseModel {
		return $this->where('plot', ...$data);
	}

	public function whereLength(...$data): GreenhouseModel {
		return $this->where('length', ...$data);
	}

	public function whereWidth(...$data): GreenhouseModel {
		return $this->where('width', ...$data);
	}

	public function whereArea(...$data): GreenhouseModel {
		return $this->where('area', ...$data);
	}

	public function whereSeasonFirst(...$data): GreenhouseModel {
		return $this->where('seasonFirst', ...$data);
	}

	public function whereSeasonLast(...$data): GreenhouseModel {
		return $this->where('seasonLast', ...$data);
	}

	public function whereCreatedAt(...$data): GreenhouseModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): GreenhouseModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class GreenhouseCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Greenhouse {

		$e = new Greenhouse();

		if(empty($id)) {
			Greenhouse::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Greenhouse::getSelection();
		}

		if(Greenhouse::model()
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
			$properties = Greenhouse::getSelection();
		}

		if($sort !== NULL) {
			Greenhouse::model()->sort($sort);
		}

		return Greenhouse::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Greenhouse {

		return new Greenhouse(['id' => NULL]);

	}

	public static function create(Greenhouse $e): void {

		Greenhouse::model()->insert($e);

	}

	public static function update(Greenhouse $e, array $properties): void {

		$e->expects(['id']);

		Greenhouse::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Greenhouse $e, array $properties): void {

		Greenhouse::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Greenhouse $e): void {

		$e->expects(['id']);

		Greenhouse::model()->delete($e);

	}

}


class GreenhousePage extends \ModulePage {

	protected string $module = 'map\Greenhouse';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? GreenhouseLib::getPropertiesCreate(),
		   $propertiesUpdate ?? GreenhouseLib::getPropertiesUpdate()
		);
	}

}
?>