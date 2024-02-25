<?php
namespace shop;

abstract class ProductElement extends \Element {

	use \FilterElement;

	private static ?ProductModel $model = NULL;

	public static function getSelection(): array {
		return Product::model()->getProperties();
	}

	public static function model(): ProductModel {
		if(self::$model === NULL) {
			self::$model = new ProductModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Product::'.$failName, $arguments, $wrapper);
	}

}


class ProductModel extends \ModuleModel {

	protected string $module = 'shop\Product';
	protected string $package = 'shop';
	protected string $table = 'shopProduct';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
			'date' => ['element32', 'shop\Date', 'cast' => 'element'],
			'product' => ['element32', 'selling\Product', 'cast' => 'element'],
			'stock' => ['float32', 'null' => TRUE, 'cast' => 'float'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'shop', 'date', 'product', 'stock'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
			'date' => 'shop\Date',
			'product' => 'selling\Product',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['product']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['date', 'product']
		]);

	}

	public function select(...$fields): ProductModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ProductModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ProductModel {
		return $this->where('id', ...$data);
	}

	public function whereShop(...$data): ProductModel {
		return $this->where('shop', ...$data);
	}

	public function whereDate(...$data): ProductModel {
		return $this->where('date', ...$data);
	}

	public function whereProduct(...$data): ProductModel {
		return $this->where('product', ...$data);
	}

	public function whereStock(...$data): ProductModel {
		return $this->where('stock', ...$data);
	}


}


abstract class ProductCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Product {

		$e = new Product();

		if(empty($id)) {
			Product::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Product::getSelection();
		}

		if(Product::model()
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
			$properties = Product::getSelection();
		}

		if($sort !== NULL) {
			Product::model()->sort($sort);
		}

		return Product::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Product {

		return new Product(['id' => NULL]);

	}

	public static function create(Product $e): void {

		Product::model()->insert($e);

	}

	public static function update(Product $e, array $properties): void {

		$e->expects(['id']);

		Product::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Product $e, array $properties): void {

		Product::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Product $e): void {

		$e->expects(['id']);

		Product::model()->delete($e);

	}

}


class ProductPage extends \ModulePage {

	protected string $module = 'shop\Product';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ProductLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ProductLib::getPropertiesUpdate()
		);
	}

}
?>