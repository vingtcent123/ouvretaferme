<?php
namespace selling;

abstract class ProductElement extends \Element {

	use \FilterElement;

	private static ?ProductModel $model = NULL;

	const COMPOSITION = 'composition';
	const UNPROCESSED_PLANT = 'unprocessed-plant';
	const UNPROCESSED_ANIMAL = 'unprocessed-animal';
	const PROCESSED_FOOD = 'processed-food';
	const PROCESSED_PRODUCT = 'processed-product';
	const OTHER = 'other';

	const PUBLIC = 'public';
	const PRIVATE = 'private';

	const ORGANIC = 'organic';
	const NATURE_PROGRES = 'nature-progres';
	const CONVERSION = 'conversion';

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';
	const DELETED = 'deleted';

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
			'profile' => ['enum', [\selling\Product::COMPOSITION, \selling\Product::UNPROCESSED_PLANT, \selling\Product::UNPROCESSED_ANIMAL, \selling\Product::PROCESSED_FOOD, \selling\Product::PROCESSED_PRODUCT, \selling\Product::OTHER], 'cast' => 'enum'],
			'category' => ['element32', 'selling\Category', 'null' => TRUE, 'cast' => 'element'],
			'unprocessedPlant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'unprocessedVariety' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'unprocessedSize' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'mixedFrozen' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'processedPackaging' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'processedAllergen' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'processedComposition' => ['text16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'compositionVisibility' => ['enum', [\selling\Product::PUBLIC, \selling\Product::PRIVATE], 'null' => TRUE, 'cast' => 'enum'],
			'origin' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'unit' => ['element32', 'selling\Unit', 'null' => TRUE, 'cast' => 'element'],
			'private' => ['bool', 'cast' => 'bool'],
			'privatePrice' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'privatePriceInitial' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'privateStep' => ['decimal', 'digits' => 6, 'decimal' => 2, 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'pro' => ['bool', 'cast' => 'bool'],
			'proPrice' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'proPriceInitial' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'proPackaging' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'proStep' => ['decimal', 'digits' => 6, 'decimal' => 2, 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'vat' => ['int8', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'quality' => ['enum', [\selling\Product::ORGANIC, \selling\Product::NATURE_PROGRES, \selling\Product::CONVERSION], 'null' => TRUE, 'cast' => 'enum'],
			'stock' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'stockLast' => ['element32', 'selling\Stock', 'null' => TRUE, 'cast' => 'element'],
			'stockUpdatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\selling\Product::ACTIVE, \selling\Product::INACTIVE, \selling\Product::DELETED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'description', 'vignette', 'profile', 'category', 'unprocessedPlant', 'unprocessedVariety', 'unprocessedSize', 'mixedFrozen', 'processedPackaging', 'processedAllergen', 'processedComposition', 'compositionVisibility', 'origin', 'farm', 'unit', 'private', 'privatePrice', 'privatePriceInitial', 'privateStep', 'pro', 'proPrice', 'proPriceInitial', 'proPackaging', 'proStep', 'vat', 'quality', 'stock', 'stockLast', 'stockUpdatedAt', 'createdAt', 'status'
		]);

		$this->propertiesToModule += [
			'category' => 'selling\Category',
			'unprocessedPlant' => 'plant\Plant',
			'farm' => 'farm\Farm',
			'unit' => 'selling\Unit',
			'stockLast' => 'selling\Stock',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['unprocessedPlant']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'unit' :
				return \selling\SellingSetting::UNIT_DEFAULT_ID;

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

			case 'profile' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'compositionVisibility' :
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

	public function whereProfile(...$data): ProductModel {
		return $this->where('profile', ...$data);
	}

	public function whereCategory(...$data): ProductModel {
		return $this->where('category', ...$data);
	}

	public function whereUnprocessedPlant(...$data): ProductModel {
		return $this->where('unprocessedPlant', ...$data);
	}

	public function whereUnprocessedVariety(...$data): ProductModel {
		return $this->where('unprocessedVariety', ...$data);
	}

	public function whereUnprocessedSize(...$data): ProductModel {
		return $this->where('unprocessedSize', ...$data);
	}

	public function whereMixedFrozen(...$data): ProductModel {
		return $this->where('mixedFrozen', ...$data);
	}

	public function whereProcessedPackaging(...$data): ProductModel {
		return $this->where('processedPackaging', ...$data);
	}

	public function whereProcessedAllergen(...$data): ProductModel {
		return $this->where('processedAllergen', ...$data);
	}

	public function whereProcessedComposition(...$data): ProductModel {
		return $this->where('processedComposition', ...$data);
	}

	public function whereCompositionVisibility(...$data): ProductModel {
		return $this->where('compositionVisibility', ...$data);
	}

	public function whereOrigin(...$data): ProductModel {
		return $this->where('origin', ...$data);
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

	public function wherePrivatePriceInitial(...$data): ProductModel {
		return $this->where('privatePriceInitial', ...$data);
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

	public function whereProPriceInitial(...$data): ProductModel {
		return $this->where('proPriceInitial', ...$data);
	}

	public function whereProPackaging(...$data): ProductModel {
		return $this->where('proPackaging', ...$data);
	}

	public function whereProStep(...$data): ProductModel {
		return $this->where('proStep', ...$data);
	}

	public function whereVat(...$data): ProductModel {
		return $this->where('vat', ...$data);
	}

	public function whereQuality(...$data): ProductModel {
		return $this->where('quality', ...$data);
	}

	public function whereStock(...$data): ProductModel {
		return $this->where('stock', ...$data);
	}

	public function whereStockLast(...$data): ProductModel {
		return $this->where('stockLast', ...$data);
	}

	public function whereStockUpdatedAt(...$data): ProductModel {
		return $this->where('stockUpdatedAt', ...$data);
	}

	public function whereCreatedAt(...$data): ProductModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): ProductModel {
		return $this->where('status', ...$data);
	}


}


abstract class ProductCrud extends \ModuleCrud {

 private static array $cache = [];

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

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

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