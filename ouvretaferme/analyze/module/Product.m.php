<?php
namespace analyze;

abstract class ProductElement extends \Element {

	use \FilterElement;

	private static ?ProductModel $model = NULL;

	const KG = 'kg';
	const UNIT = 'unit';
	const BUNCH = 'bunch';

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

	protected string $module = 'analyze\Product';
	protected string $package = 'analyze';
	protected string $table = 'analyzeProduct';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'report' => ['element32', 'analyze\Report', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'product' => ['element32', 'selling\Product', 'cast' => 'element'],
			'unit' => ['enum', [\analyze\Product::KG, \analyze\Product::UNIT, \analyze\Product::BUNCH], 'cast' => 'enum'],
			'turnover' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'quantity' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'report', 'farm', 'product', 'unit', 'turnover', 'quantity'
		]);

		$this->propertiesToModule += [
			'report' => 'analyze\Report',
			'farm' => 'farm\Farm',
			'product' => 'selling\Product',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['report']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'turnover' :
				return 0;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'unit' :
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

	public function whereReport(...$data): ProductModel {
		return $this->where('report', ...$data);
	}

	public function whereFarm(...$data): ProductModel {
		return $this->where('farm', ...$data);
	}

	public function whereProduct(...$data): ProductModel {
		return $this->where('product', ...$data);
	}

	public function whereUnit(...$data): ProductModel {
		return $this->where('unit', ...$data);
	}

	public function whereTurnover(...$data): ProductModel {
		return $this->where('turnover', ...$data);
	}

	public function whereQuantity(...$data): ProductModel {
		return $this->where('quantity', ...$data);
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

	protected string $module = 'analyze\Product';

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