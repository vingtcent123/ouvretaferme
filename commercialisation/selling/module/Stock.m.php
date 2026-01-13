<?php
namespace selling;

abstract class StockElement extends \Element {

	use \FilterElement;

	private static ?StockModel $model = NULL;

	public static function getSelection(): array {
		return Stock::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): StockModel {
		if(self::$model === NULL) {
			self::$model = new StockModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Stock::'.$failName, $arguments, $wrapper);
	}

}


class StockModel extends \ModuleModel {

	protected string $module = 'selling\Stock';
	protected string $package = 'selling';
	protected string $table = 'sellingStock';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'product' => ['element32', 'selling\Product', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'newValue' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.0, 'max' => 999999.99, 'cast' => 'float'],
			'delta' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.0, 'max' => 999999.99, 'cast' => 'float'],
			'comment' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'product', 'farm', 'newValue', 'delta', 'comment', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'product' => 'selling\Product',
			'farm' => 'farm\Farm',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['product']
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

	public function select(...$fields): StockModel {
		return parent::select(...$fields);
	}

	public function where(...$data): StockModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): StockModel {
		return $this->where('id', ...$data);
	}

	public function whereProduct(...$data): StockModel {
		return $this->where('product', ...$data);
	}

	public function whereFarm(...$data): StockModel {
		return $this->where('farm', ...$data);
	}

	public function whereNewValue(...$data): StockModel {
		return $this->where('newValue', ...$data);
	}

	public function whereDelta(...$data): StockModel {
		return $this->where('delta', ...$data);
	}

	public function whereComment(...$data): StockModel {
		return $this->where('comment', ...$data);
	}

	public function whereCreatedAt(...$data): StockModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): StockModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class StockCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Stock {

		$e = new Stock();

		if(empty($id)) {
			Stock::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Stock::getSelection();
		}

		if(Stock::model()
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
			$properties = Stock::getSelection();
		}

		if($sort !== NULL) {
			Stock::model()->sort($sort);
		}

		return Stock::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Stock {

		return new Stock(['id' => NULL]);

	}

	public static function create(Stock $e): void {

		Stock::model()->insert($e);

	}

	public static function update(Stock $e, array $properties): void {

		$e->expects(['id']);

		Stock::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Stock $e, array $properties): void {

		Stock::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Stock $e): void {

		$e->expects(['id']);

		Stock::model()->delete($e);

	}

}


class StockPage extends \ModulePage {

	protected string $module = 'selling\Stock';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? StockLib::getPropertiesCreate(),
		   $propertiesUpdate ?? StockLib::getPropertiesUpdate()
		);
	}

}
?>