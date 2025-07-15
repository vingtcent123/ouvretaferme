<?php
namespace journal;

abstract class StockElement extends \Element {

	use \FilterElement;

	private static ?StockModel $model = NULL;

	public static function getSelection(): array {
		return Stock::model()->getProperties();
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

	protected string $module = 'journal\Stock';
	protected string $package = 'journal';
	protected string $table = 'journalStock';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'type' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'account' => ['element32', 'account\Account', 'cast' => 'element'],
			'accountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'variationAccount' => ['element32', 'account\Account', 'cast' => 'element'],
			'variationAccountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'initialStock' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0, 'max' => NULL, 'cast' => 'float'],
			'finalStock' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0, 'max' => NULL, 'cast' => 'float'],
			'variation' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'operation' => ['element32', 'journal\Operation', 'null' => TRUE, 'cast' => 'element'],
			'reportedTo' => ['element32', 'journal\Stock', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'financialYear', 'type', 'account', 'accountLabel', 'variationAccount', 'variationAccountLabel', 'initialStock', 'finalStock', 'variation', 'operation', 'reportedTo', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'financialYear' => 'account\FinancialYear',
			'account' => 'account\Account',
			'variationAccount' => 'account\Account',
			'operation' => 'journal\Operation',
			'reportedTo' => 'journal\Stock',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['financialYear']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
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

	public function whereFinancialYear(...$data): StockModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereType(...$data): StockModel {
		return $this->where('type', ...$data);
	}

	public function whereAccount(...$data): StockModel {
		return $this->where('account', ...$data);
	}

	public function whereAccountLabel(...$data): StockModel {
		return $this->where('accountLabel', ...$data);
	}

	public function whereVariationAccount(...$data): StockModel {
		return $this->where('variationAccount', ...$data);
	}

	public function whereVariationAccountLabel(...$data): StockModel {
		return $this->where('variationAccountLabel', ...$data);
	}

	public function whereInitialStock(...$data): StockModel {
		return $this->where('initialStock', ...$data);
	}

	public function whereFinalStock(...$data): StockModel {
		return $this->where('finalStock', ...$data);
	}

	public function whereVariation(...$data): StockModel {
		return $this->where('variation', ...$data);
	}

	public function whereOperation(...$data): StockModel {
		return $this->where('operation', ...$data);
	}

	public function whereReportedTo(...$data): StockModel {
		return $this->where('reportedTo', ...$data);
	}

	public function whereCreatedAt(...$data): StockModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): StockModel {
		return $this->where('updatedAt', ...$data);
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

	protected string $module = 'journal\Stock';

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