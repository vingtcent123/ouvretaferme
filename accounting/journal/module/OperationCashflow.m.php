<?php
namespace journal;

abstract class OperationCashflowElement extends \Element {

	use \FilterElement;

	private static ?OperationCashflowModel $model = NULL;

	public static function getSelection(): array {
		return OperationCashflow::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): OperationCashflowModel {
		if(self::$model === NULL) {
			self::$model = new OperationCashflowModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('OperationCashflow::'.$failName, $arguments, $wrapper);
	}

}


class OperationCashflowModel extends \ModuleModel {

	protected string $module = 'journal\OperationCashflow';
	protected string $package = 'journal';
	protected string $table = 'journalOperationCashflow';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'operation' => ['element32', 'journal\Operation', 'cast' => 'element'],
			'cashflow' => ['element32', 'bank\Cashflow', 'cast' => 'element'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'operation', 'cashflow', 'amount'
		]);

		$this->propertiesToModule += [
			'operation' => 'journal\Operation',
			'cashflow' => 'bank\Cashflow',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['operation'],
			['cashflow']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['operation', 'cashflow']
		]);

	}

	public function select(...$fields): OperationCashflowModel {
		return parent::select(...$fields);
	}

	public function where(...$data): OperationCashflowModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): OperationCashflowModel {
		return $this->where('id', ...$data);
	}

	public function whereOperation(...$data): OperationCashflowModel {
		return $this->where('operation', ...$data);
	}

	public function whereCashflow(...$data): OperationCashflowModel {
		return $this->where('cashflow', ...$data);
	}

	public function whereAmount(...$data): OperationCashflowModel {
		return $this->where('amount', ...$data);
	}


}


abstract class OperationCashflowCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): OperationCashflow {

		$e = new OperationCashflow();

		if(empty($id)) {
			OperationCashflow::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = OperationCashflow::getSelection();
		}

		if(OperationCashflow::model()
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
			$properties = OperationCashflow::getSelection();
		}

		if($sort !== NULL) {
			OperationCashflow::model()->sort($sort);
		}

		return OperationCashflow::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): OperationCashflow {

		return new OperationCashflow(['id' => NULL]);

	}

	public static function create(OperationCashflow $e): void {

		OperationCashflow::model()->insert($e);

	}

	public static function update(OperationCashflow $e, array $properties): void {

		$e->expects(['id']);

		OperationCashflow::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, OperationCashflow $e, array $properties): void {

		OperationCashflow::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(OperationCashflow $e): void {

		$e->expects(['id']);

		OperationCashflow::model()->delete($e);

	}

}


class OperationCashflowPage extends \ModulePage {

	protected string $module = 'journal\OperationCashflow';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? OperationCashflowLib::getPropertiesCreate(),
		   $propertiesUpdate ?? OperationCashflowLib::getPropertiesUpdate()
		);
	}

}
?>