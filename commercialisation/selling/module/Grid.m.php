<?php
namespace selling;

abstract class GridElement extends \Element {

	use \FilterElement;

	private static ?GridModel $model = NULL;

	public static function getSelection(): array {
		return Grid::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): GridModel {
		if(self::$model === NULL) {
			self::$model = new GridModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Grid::'.$failName, $arguments, $wrapper);
	}

}


class GridModel extends \ModuleModel {

	protected string $module = 'selling\Grid';
	protected string $package = 'selling';
	protected string $table = 'sellingGrid';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'group' => ['element32', 'selling\CustomerGroup', 'null' => TRUE, 'cast' => 'element'],
			'product' => ['element32', 'selling\Product', 'cast' => 'element'],
			'price' => ['decimal', 'digits' => 10, 'decimal' => 4, 'min' => 0.0, 'max' => 999999.9999, 'cast' => 'float'],
			'priceInitial' => ['decimal', 'digits' => 10, 'decimal' => 4, 'min' => 0.0, 'max' => 999999.9999, 'null' => TRUE, 'cast' => 'float'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'customer', 'group', 'product', 'price', 'priceInitial', 'createdAt', 'updatedAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'customer' => 'selling\Customer',
			'group' => 'selling\CustomerGroup',
			'product' => 'selling\Product',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['product']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['customer', 'product'],
			['group', 'product']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): GridModel {
		return parent::select(...$fields);
	}

	public function where(...$data): GridModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): GridModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): GridModel {
		return $this->where('farm', ...$data);
	}

	public function whereCustomer(...$data): GridModel {
		return $this->where('customer', ...$data);
	}

	public function whereGroup(...$data): GridModel {
		return $this->where('group', ...$data);
	}

	public function whereProduct(...$data): GridModel {
		return $this->where('product', ...$data);
	}

	public function wherePrice(...$data): GridModel {
		return $this->where('price', ...$data);
	}

	public function wherePriceInitial(...$data): GridModel {
		return $this->where('priceInitial', ...$data);
	}

	public function whereCreatedAt(...$data): GridModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): GridModel {
		return $this->where('updatedAt', ...$data);
	}


}


abstract class GridCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Grid {

		$e = new Grid();

		if(empty($id)) {
			Grid::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Grid::getSelection();
		}

		if(Grid::model()
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
			$properties = Grid::getSelection();
		}

		if($sort !== NULL) {
			Grid::model()->sort($sort);
		}

		return Grid::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Grid {

		return new Grid($properties);

	}

	public static function create(Grid $e): void {

		Grid::model()->insert($e);

	}

	public static function update(Grid $e, array $properties): void {

		$e->expects(['id']);

		Grid::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Grid $e, array $properties): void {

		Grid::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Grid $e): void {

		$e->expects(['id']);

		Grid::model()->delete($e);

	}

}


class GridPage extends \ModulePage {

	protected string $module = 'selling\Grid';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? GridLib::getPropertiesCreate(),
		   $propertiesUpdate ?? GridLib::getPropertiesUpdate()
		);
	}

}
?>