<?php
namespace journal;

abstract class AccruedIncomeElement extends \Element {

	use \FilterElement;

	private static ?AccruedIncomeModel $model = NULL;

	const PLANNED = 'planned';
	const RECORDED = 'recorded';
	const ACCRUED = 'accrued';
	const CANCELLED = 'cancelled';

	public static function getSelection(): array {
		return AccruedIncome::model()->getProperties();
	}

	public static function model(): AccruedIncomeModel {
		if(self::$model === NULL) {
			self::$model = new AccruedIncomeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('AccruedIncome::'.$failName, $arguments, $wrapper);
	}

}


class AccruedIncomeModel extends \ModuleModel {

	protected string $module = 'journal\AccruedIncome';
	protected string $package = 'journal';
	protected string $table = 'journalAccruedIncome';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'operationClosing' => ['element32', 'journal\Operation', 'null' => TRUE, 'cast' => 'element'],
			'operationOpening' => ['element32', 'journal\Operation', 'null' => TRUE, 'cast' => 'element'],
			'operationClearing' => ['element32', 'journal\Operation', 'null' => TRUE, 'cast' => 'element'],
			'account' => ['element32', 'account\Account', 'cast' => 'element'],
			'accountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'thirdParty' => ['element32', 'account\ThirdParty', 'null' => TRUE, 'cast' => 'element'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'date' => ['date', 'cast' => 'string'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.01, 'max' => NULL, 'cast' => 'float'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'destinationFinancialYear' => ['element32', 'account\FinancialYear', 'null' => TRUE, 'cast' => 'element'],
			'status' => ['enum', [\journal\AccruedIncome::PLANNED, \journal\AccruedIncome::RECORDED, \journal\AccruedIncome::ACCRUED, \journal\AccruedIncome::CANCELLED], 'cast' => 'enum'],
			'isCleared' => ['bool', 'cast' => 'bool'],
			'clearingDate' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'clearedBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'operationClosing', 'operationOpening', 'operationClearing', 'account', 'accountLabel', 'thirdParty', 'description', 'date', 'amount', 'financialYear', 'destinationFinancialYear', 'status', 'isCleared', 'clearingDate', 'clearedBy', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'operationClosing' => 'journal\Operation',
			'operationOpening' => 'journal\Operation',
			'operationClearing' => 'journal\Operation',
			'account' => 'account\Account',
			'thirdParty' => 'account\ThirdParty',
			'financialYear' => 'account\FinancialYear',
			'destinationFinancialYear' => 'account\FinancialYear',
			'clearedBy' => 'user\User',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['financialYear'],
			['status']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return AccruedIncome::PLANNED;

			case 'isCleared' :
				return FALSE;

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

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): AccruedIncomeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): AccruedIncomeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): AccruedIncomeModel {
		return $this->where('id', ...$data);
	}

	public function whereOperationClosing(...$data): AccruedIncomeModel {
		return $this->where('operationClosing', ...$data);
	}

	public function whereOperationOpening(...$data): AccruedIncomeModel {
		return $this->where('operationOpening', ...$data);
	}

	public function whereOperationClearing(...$data): AccruedIncomeModel {
		return $this->where('operationClearing', ...$data);
	}

	public function whereAccount(...$data): AccruedIncomeModel {
		return $this->where('account', ...$data);
	}

	public function whereAccountLabel(...$data): AccruedIncomeModel {
		return $this->where('accountLabel', ...$data);
	}

	public function whereThirdParty(...$data): AccruedIncomeModel {
		return $this->where('thirdParty', ...$data);
	}

	public function whereDescription(...$data): AccruedIncomeModel {
		return $this->where('description', ...$data);
	}

	public function whereDate(...$data): AccruedIncomeModel {
		return $this->where('date', ...$data);
	}

	public function whereAmount(...$data): AccruedIncomeModel {
		return $this->where('amount', ...$data);
	}

	public function whereFinancialYear(...$data): AccruedIncomeModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereDestinationFinancialYear(...$data): AccruedIncomeModel {
		return $this->where('destinationFinancialYear', ...$data);
	}

	public function whereStatus(...$data): AccruedIncomeModel {
		return $this->where('status', ...$data);
	}

	public function whereIsCleared(...$data): AccruedIncomeModel {
		return $this->where('isCleared', ...$data);
	}

	public function whereClearingDate(...$data): AccruedIncomeModel {
		return $this->where('clearingDate', ...$data);
	}

	public function whereClearedBy(...$data): AccruedIncomeModel {
		return $this->where('clearedBy', ...$data);
	}

	public function whereCreatedAt(...$data): AccruedIncomeModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): AccruedIncomeModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereCreatedBy(...$data): AccruedIncomeModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class AccruedIncomeCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): AccruedIncome {

		$e = new AccruedIncome();

		if(empty($id)) {
			AccruedIncome::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = AccruedIncome::getSelection();
		}

		if(AccruedIncome::model()
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
			$properties = AccruedIncome::getSelection();
		}

		if($sort !== NULL) {
			AccruedIncome::model()->sort($sort);
		}

		return AccruedIncome::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): AccruedIncome {

		return new AccruedIncome(['id' => NULL]);

	}

	public static function create(AccruedIncome $e): void {

		AccruedIncome::model()->insert($e);

	}

	public static function update(AccruedIncome $e, array $properties): void {

		$e->expects(['id']);

		AccruedIncome::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, AccruedIncome $e, array $properties): void {

		AccruedIncome::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(AccruedIncome $e): void {

		$e->expects(['id']);

		AccruedIncome::model()->delete($e);

	}

}


class AccruedIncomePage extends \ModulePage {

	protected string $module = 'journal\AccruedIncome';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? AccruedIncomeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? AccruedIncomeLib::getPropertiesUpdate()
		);
	}

}
?>