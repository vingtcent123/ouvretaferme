<?php
namespace shop;

abstract class CategoryElement extends \Element {

	use \FilterElement;

	private static ?CategoryModel $model = NULL;

	public static function getSelection(): array {
		return Category::model()->getProperties();
	}

	public static function model(): CategoryModel {
		if(self::$model === NULL) {
			self::$model = new CategoryModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Category::'.$failName, $arguments, $wrapper);
	}

}


class CategoryModel extends \ModuleModel {

	protected string $module = 'shop\Category';
	protected string $package = 'shop';
	protected string $table = 'shopCategory';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'null' => TRUE, 'cast' => 'string'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'shop'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['shop']
		]);

	}

	public function select(...$fields): CategoryModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CategoryModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CategoryModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): CategoryModel {
		return $this->where('name', ...$data);
	}

	public function whereShop(...$data): CategoryModel {
		return $this->where('shop', ...$data);
	}


}


abstract class CategoryCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Category {

		$e = new Category();

		if(empty($id)) {
			Category::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Category::getSelection();
		}

		if(Category::model()
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
			$properties = Category::getSelection();
		}

		if($sort !== NULL) {
			Category::model()->sort($sort);
		}

		return Category::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Category {

		return new Category(['id' => NULL]);

	}

	public static function create(Category $e): void {

		Category::model()->insert($e);

	}

	public static function update(Category $e, array $properties): void {

		$e->expects(['id']);

		Category::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Category $e, array $properties): void {

		Category::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Category $e): void {

		$e->expects(['id']);

		Category::model()->delete($e);

	}

}


class CategoryPage extends \ModulePage {

	protected string $module = 'shop\Category';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CategoryLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CategoryLib::getPropertiesUpdate()
		);
	}

}
?>