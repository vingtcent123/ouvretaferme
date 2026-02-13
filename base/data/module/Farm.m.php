<?php
namespace data;

abstract class FarmElement extends \Element {

	use \FilterElement;

	private static ?FarmModel $model = NULL;

	public static function getSelection(): array {
		return Farm::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'data\Farm';
	protected string $package = 'data';
	protected string $table = 'dataFarm';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'data' => ['element32', 'data\Data', 'cast' => 'element'],
			'value' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'farm', 'data', 'value', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'data' => 'data\Data',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'data']
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

	public function select(...$fields): FarmModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FarmModel {
		return parent::where(...$data);
	}

	public function whereFarm(...$data): FarmModel {
		return $this->where('farm', ...$data);
	}

	public function whereData(...$data): FarmModel {
		return $this->where('data', ...$data);
	}

	public function whereValue(...$data): FarmModel {
		return $this->where('value', ...$data);
	}

	public function whereCreatedAt(...$data): FarmModel {
		return $this->where('createdAt', ...$data);
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

	public static function getNewElement(array $properties = []): Farm {

		return new Farm($properties);

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

	protected string $module = 'data\Farm';

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