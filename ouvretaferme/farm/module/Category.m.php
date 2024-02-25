<?php
namespace farm;

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

	protected string $module = 'farm\Category';
	protected string $package = 'farm';
	protected string $table = 'farmCategory';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'fqn' => ['text8', 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'position' => ['int8', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'fqn', 'farm', 'position'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
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

	public function whereFqn(...$data): CategoryModel {
		return $this->where('fqn', ...$data);
	}

	public function whereFarm(...$data): CategoryModel {
		return $this->where('farm', ...$data);
	}

	public function wherePosition(...$data): CategoryModel {
		return $this->where('position', ...$data);
	}


}


abstract class CategoryCrud extends \ModuleCrud {

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

	protected string $module = 'farm\Category';

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