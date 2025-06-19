<?php
namespace shop;

abstract class DepartmentElement extends \Element {

	use \FilterElement;

	private static ?DepartmentModel $model = NULL;

	public static function getSelection(): array {
		return Department::model()->getProperties();
	}

	public static function model(): DepartmentModel {
		if(self::$model === NULL) {
			self::$model = new DepartmentModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Department::'.$failName, $arguments, $wrapper);
	}

}


class DepartmentModel extends \ModuleModel {

	protected string $module = 'shop\Department';
	protected string $package = 'shop';
	protected string $table = 'shopDepartment';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'icon' => ['text8', 'min' => 1, 'max' => 50, 'charset' => 'ascii', 'cast' => 'string'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'collate' => 'general', 'cast' => 'string'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
			'catalogs' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'position' => ['int8', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'icon', 'name', 'shop', 'catalogs', 'position'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['shop']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'catalogs' :
				return [];

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'catalogs' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'catalogs' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): DepartmentModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DepartmentModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DepartmentModel {
		return $this->where('id', ...$data);
	}

	public function whereIcon(...$data): DepartmentModel {
		return $this->where('icon', ...$data);
	}

	public function whereName(...$data): DepartmentModel {
		return $this->where('name', ...$data);
	}

	public function whereShop(...$data): DepartmentModel {
		return $this->where('shop', ...$data);
	}

	public function whereCatalogs(...$data): DepartmentModel {
		return $this->where('catalogs', ...$data);
	}

	public function wherePosition(...$data): DepartmentModel {
		return $this->where('position', ...$data);
	}


}


abstract class DepartmentCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Department {

		$e = new Department();

		if(empty($id)) {
			Department::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Department::getSelection();
		}

		if(Department::model()
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
			$properties = Department::getSelection();
		}

		if($sort !== NULL) {
			Department::model()->sort($sort);
		}

		return Department::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Department {

		return new Department(['id' => NULL]);

	}

	public static function create(Department $e): void {

		Department::model()->insert($e);

	}

	public static function update(Department $e, array $properties): void {

		$e->expects(['id']);

		Department::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Department $e, array $properties): void {

		Department::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Department $e): void {

		$e->expects(['id']);

		Department::model()->delete($e);

	}

}


class DepartmentPage extends \ModulePage {

	protected string $module = 'shop\Department';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DepartmentLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DepartmentLib::getPropertiesUpdate()
		);
	}

}
?>