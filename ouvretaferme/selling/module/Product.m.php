<?php
namespace selling;

abstract class ProductElement extends \Element {

	use \FilterElement;

	private static ?ProductModel $model = NULL;

	const KG = 'kg';
	const GRAM = 'gram';
	const GRAM_100 = 'gram-100';
	const GRAM_250 = 'gram-250';
	const GRAM_500 = 'gram-500';
	const UNIT = 'unit';
	const BUNCH = 'bunch';
	const PLANT = 'plant';

	const ORGANIC = 'organic';
	const NATURE_PROGRES = 'nature-progres';
	const CONVERSION = 'conversion';

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

	protected string $module = 'selling\Product';
	protected string $package = 'selling';
	protected string $table = 'sellingProduct';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'description' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'vignette' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'plant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'variety' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'size' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'unit' => ['enum', [\selling\Product::KG, \selling\Product::GRAM, \selling\Product::GRAM_100, \selling\Product::GRAM_250, \selling\Product::GRAM_500, \selling\Product::UNIT, \selling\Product::BUNCH, \selling\Product::PLANT], 'cast' => 'enum'],
			'private' => ['bool', 'cast' => 'bool'],
			'privatePrice' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'privateStep' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'pro' => ['bool', 'cast' => 'bool'],
			'proPrice' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'proPackaging' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'vat' => ['int8', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'quality' => ['enum', [\selling\Product::ORGANIC, \selling\Product::NATURE_PROGRES, \selling\Product::CONVERSION], 'null' => TRUE, 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\selling\Product::ACTIVE, \selling\Product::INACTIVE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'description', 'vignette', 'plant', 'variety', 'size', 'farm', 'unit', 'private', 'privatePrice', 'privateStep', 'pro', 'proPrice', 'proPackaging', 'vat', 'quality', 'createdAt', 'status'
		]);

		$this->propertiesToModule += [
			'plant' => 'plant\Plant',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'unit' :
				return Product::KG;

			case 'private' :
				return TRUE;

			case 'pro' :
				return TRUE;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Product::ACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'unit' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'quality' :
				return ($value === NULL) ? NULL : (string)$value;

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

	public function whereName(...$data): ProductModel {
		return $this->where('name', ...$data);
	}

	public function whereDescription(...$data): ProductModel {
		return $this->where('description', ...$data);
	}

	public function whereVignette(...$data): ProductModel {
		return $this->where('vignette', ...$data);
	}

	public function wherePlant(...$data): ProductModel {
		return $this->where('plant', ...$data);
	}

	public function whereVariety(...$data): ProductModel {
		return $this->where('variety', ...$data);
	}

	public function whereSize(...$data): ProductModel {
		return $this->where('size', ...$data);
	}

	public function whereFarm(...$data): ProductModel {
		return $this->where('farm', ...$data);
	}

	public function whereUnit(...$data): ProductModel {
		return $this->where('unit', ...$data);
	}

	public function wherePrivate(...$data): ProductModel {
		return $this->where('private', ...$data);
	}

	public function wherePrivatePrice(...$data): ProductModel {
		return $this->where('privatePrice', ...$data);
	}

	public function wherePrivateStep(...$data): ProductModel {
		return $this->where('privateStep', ...$data);
	}

	public function wherePro(...$data): ProductModel {
		return $this->where('pro', ...$data);
	}

	public function whereProPrice(...$data): ProductModel {
		return $this->where('proPrice', ...$data);
	}

	public function whereProPackaging(...$data): ProductModel {
		return $this->where('proPackaging', ...$data);
	}

	public function whereVat(...$data): ProductModel {
		return $this->where('vat', ...$data);
	}

	public function whereQuality(...$data): ProductModel {
		return $this->where('quality', ...$data);
	}

	public function whereCreatedAt(...$data): ProductModel {
		return $this->where('createdAt', ...$data);
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

	protected string $module = 'selling\Product';

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