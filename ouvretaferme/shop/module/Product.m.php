<?php
namespace shop;

abstract class ProductElement extends \Element {

	use \FilterElement;

	private static ?ProductModel $model = NULL;

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

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
			'date' => ['element32', 'shop\Date', 'null' => TRUE, 'cast' => 'element'],
			'catalog' => ['element32', 'shop\Catalog', 'null' => TRUE, 'cast' => 'element'],
			'product' => ['element32', 'selling\Product', 'cast' => 'element'],
			'packaging' => ['decimal', 'digits' => 6, 'decimal' => 2, 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'packagingCustom' => ['bool', 'cast' => 'bool'],
			'price' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.0, 'max' => NULL, 'cast' => 'float'],
			'priceCustom' => ['bool', 'cast' => 'bool'],
			'saleStartAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'saleEndAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'available' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'status' => ['enum', [\shop\Product::ACTIVE, \shop\Product::INACTIVE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'shop', 'date', 'catalog', 'product', 'packaging', 'packagingCustom', 'price', 'priceCustom', 'saleStartAt', 'saleEndAt', 'available', 'status'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
			'date' => 'shop\Date',
			'catalog' => 'shop\Catalog',
			'product' => 'selling\Product',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['product'],
			['catalog']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['date', 'product'],
			['catalog', 'product']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'packagingCustom' :
				return FALSE;

			case 'priceCustom' :
				return FALSE;

			case 'status' :
				return Product::ACTIVE;

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

	public function whereCatalog(...$data): ProductModel {
		return $this->where('catalog', ...$data);
	}

	public function whereProduct(...$data): ProductModel {
		return $this->where('product', ...$data);
	}

	public function wherePackaging(...$data): ProductModel {
		return $this->where('packaging', ...$data);
	}

	public function wherePackagingCustom(...$data): ProductModel {
		return $this->where('packagingCustom', ...$data);
	}

	public function wherePrice(...$data): ProductModel {
		return $this->where('price', ...$data);
	}

	public function wherePriceCustom(...$data): ProductModel {
		return $this->where('priceCustom', ...$data);
	}

	public function whereSaleStartAt(...$data): ProductModel {
		return $this->where('saleStartAt', ...$data);
	}

	public function whereSaleEndAt(...$data): ProductModel {
		return $this->where('saleEndAt', ...$data);
	}

	public function whereAvailable(...$data): ProductModel {
		return $this->where('available', ...$data);
	}

	public function whereStatus(...$data): ProductModel {
		return $this->where('status', ...$data);
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