<?php
namespace shop;

abstract class RangeElement extends \Element {

	use \FilterElement;

	private static ?RangeModel $model = NULL;

	const AUTO = 'auto';
	const MANUAL = 'manual';

	public static function getSelection(): array {
		return Range::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): RangeModel {
		if(self::$model === NULL) {
			self::$model = new RangeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Range::'.$failName, $arguments, $wrapper);
	}

}


class RangeModel extends \ModuleModel {

	protected string $module = 'shop\Range';
	protected string $package = 'shop';
	protected string $table = 'shopRange';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'catalog' => ['element32', 'shop\Catalog', 'cast' => 'element'],
			'department' => ['element32', 'shop\Department', 'null' => TRUE, 'cast' => 'element'],
			'status' => ['enum', [\shop\Range::AUTO, \shop\Range::MANUAL], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'shop', 'farm', 'catalog', 'department', 'status', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
			'farm' => 'farm\Farm',
			'catalog' => 'shop\Catalog',
			'department' => 'shop\Department',
			'createdBy' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['shop', 'catalog']
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

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): RangeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): RangeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): RangeModel {
		return $this->where('id', ...$data);
	}

	public function whereShop(...$data): RangeModel {
		return $this->where('shop', ...$data);
	}

	public function whereFarm(...$data): RangeModel {
		return $this->where('farm', ...$data);
	}

	public function whereCatalog(...$data): RangeModel {
		return $this->where('catalog', ...$data);
	}

	public function whereDepartment(...$data): RangeModel {
		return $this->where('department', ...$data);
	}

	public function whereStatus(...$data): RangeModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): RangeModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): RangeModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class RangeCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Range {

		$e = new Range();

		if(empty($id)) {
			Range::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Range::getSelection();
		}

		if(Range::model()
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
			$properties = Range::getSelection();
		}

		if($sort !== NULL) {
			Range::model()->sort($sort);
		}

		return Range::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Range {

		return new Range($properties);

	}

	public static function create(Range $e): void {

		Range::model()->insert($e);

	}

	public static function update(Range $e, array $properties): void {

		$e->expects(['id']);

		Range::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Range $e, array $properties): void {

		Range::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Range $e): void {

		$e->expects(['id']);

		Range::model()->delete($e);

	}

}


class RangePage extends \ModulePage {

	protected string $module = 'shop\Range';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? RangeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? RangeLib::getPropertiesUpdate()
		);
	}

}
?>