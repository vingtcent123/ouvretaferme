<?php
namespace bank;

abstract class CashflowElement extends \Element {

	use \FilterElement;

	private static ?CashflowModel $model = NULL;

	const DEBIT = 'debit';
	const CREDIT = 'credit';
	const OTHER = 'other';

	const WAITING = 'waiting';
	const ALLOCATED = 'allocated';

	public static function getSelection(): array {
		return Cashflow::model()->getProperties();
	}

	public static function model(): CashflowModel {
		if(self::$model === NULL) {
			self::$model = new CashflowModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Cashflow::'.$failName, $arguments, $wrapper);
	}

}


class CashflowModel extends \ModuleModel {

	protected string $module = 'bank\Cashflow';
	protected string $package = 'bank';
	protected string $table = 'bankCashflow';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'date' => ['date', 'min' => toDate('NOW - 2 YEARS'), 'max' => toDate('NOW + 1 YEARS'), 'null' => TRUE, 'cast' => 'string'],
			'type' => ['enum', [\bank\Cashflow::DEBIT, \bank\Cashflow::CREDIT, \bank\Cashflow::OTHER], 'cast' => 'enum'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'fitid' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'name' => ['text24', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'memo' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'account' => ['element32', 'bank\Account', 'cast' => 'element'],
			'import' => ['element32', 'bank\Import', 'cast' => 'element'],
			'status' => ['enum', [\bank\Cashflow::WAITING, \bank\Cashflow::ALLOCATED], 'cast' => 'enum'],
			'document' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'date', 'type', 'amount', 'fitid', 'name', 'memo', 'account', 'import', 'status', 'document', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'account' => 'bank\Account',
			'import' => 'bank\Import',
			'createdBy' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fitid']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Cashflow::WAITING;

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

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): CashflowModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CashflowModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CashflowModel {
		return $this->where('id', ...$data);
	}

	public function whereDate(...$data): CashflowModel {
		return $this->where('date', ...$data);
	}

	public function whereType(...$data): CashflowModel {
		return $this->where('type', ...$data);
	}

	public function whereAmount(...$data): CashflowModel {
		return $this->where('amount', ...$data);
	}

	public function whereFitid(...$data): CashflowModel {
		return $this->where('fitid', ...$data);
	}

	public function whereName(...$data): CashflowModel {
		return $this->where('name', ...$data);
	}

	public function whereMemo(...$data): CashflowModel {
		return $this->where('memo', ...$data);
	}

	public function whereAccount(...$data): CashflowModel {
		return $this->where('account', ...$data);
	}

	public function whereImport(...$data): CashflowModel {
		return $this->where('import', ...$data);
	}

	public function whereStatus(...$data): CashflowModel {
		return $this->where('status', ...$data);
	}

	public function whereDocument(...$data): CashflowModel {
		return $this->where('document', ...$data);
	}

	public function whereCreatedAt(...$data): CashflowModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): CashflowModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereCreatedBy(...$data): CashflowModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class CashflowCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Cashflow {

		$e = new Cashflow();

		if(empty($id)) {
			Cashflow::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Cashflow::getSelection();
		}

		if(Cashflow::model()
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
			$properties = Cashflow::getSelection();
		}

		if($sort !== NULL) {
			Cashflow::model()->sort($sort);
		}

		return Cashflow::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Cashflow {

		return new Cashflow(['id' => NULL]);

	}

	public static function create(Cashflow $e): void {

		Cashflow::model()->insert($e);

	}

	public static function update(Cashflow $e, array $properties): void {

		$e->expects(['id']);

		Cashflow::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Cashflow $e, array $properties): void {

		Cashflow::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Cashflow $e): void {

		$e->expects(['id']);

		Cashflow::model()->delete($e);

	}

}


class CashflowPage extends \ModulePage {

	protected string $module = 'bank\Cashflow';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CashflowLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CashflowLib::getPropertiesUpdate()
		);
	}

}
?>