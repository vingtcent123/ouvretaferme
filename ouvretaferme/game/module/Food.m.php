<?php
namespace game;

abstract class FoodElement extends \Element {

	use \FilterElement;

	private static ?FoodModel $model = NULL;

	public static function getSelection(): array {
		return Food::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): FoodModel {
		if(self::$model === NULL) {
			self::$model = new FoodModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Food::'.$failName, $arguments, $wrapper);
	}

}


class FoodModel extends \ModuleModel {

	protected string $module = 'game\Food';
	protected string $package = 'game';
	protected string $table = 'gameFood';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'growing' => ['element32', 'game\Growing', 'null' => TRUE, 'cast' => 'element'],
			'current' => ['int16', 'cast' => 'int'],
			'total' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'growing', 'current', 'total'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'growing' => 'game\Growing',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'current' :
				return 0;

			case 'total' :
				return 0;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): FoodModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FoodModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): FoodModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): FoodModel {
		return $this->where('user', ...$data);
	}

	public function whereGrowing(...$data): FoodModel {
		return $this->where('growing', ...$data);
	}

	public function whereCurrent(...$data): FoodModel {
		return $this->where('current', ...$data);
	}

	public function whereTotal(...$data): FoodModel {
		return $this->where('total', ...$data);
	}


}


abstract class FoodCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Food {

		$e = new Food();

		if(empty($id)) {
			Food::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Food::getSelection();
		}

		if(Food::model()
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
			$properties = Food::getSelection();
		}

		if($sort !== NULL) {
			Food::model()->sort($sort);
		}

		return Food::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Food {

		return new Food($properties);

	}

	public static function create(Food $e): void {

		Food::model()->insert($e);

	}

	public static function update(Food $e, array $properties): void {

		$e->expects(['id']);

		Food::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Food $e, array $properties): void {

		Food::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Food $e): void {

		$e->expects(['id']);

		Food::model()->delete($e);

	}

}


class FoodPage extends \ModulePage {

	protected string $module = 'game\Food';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FoodLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FoodLib::getPropertiesUpdate()
		);
	}

}
?>