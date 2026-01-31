<?php
namespace account;

abstract class FinancialYearElement extends \Element {

	use \FilterElement;

	private static ?FinancialYearModel $model = NULL;

	const OPEN = 'open';
	const CLOSE = 'close';

	const MONTHLY = 'monthly';
	const QUARTERLY = 'quarterly';
	const ANNUALLY = 'annually';

	const CASH = 'cash';
	const DEBIT = 'debit';

	const MICRO_BA = 'micro-ba';
	const BA_REEL_SIMPLIFIE = 'ba-reel-simplifie';
	const BA_REEL_NORMAL = 'ba-reel-normal';
	const AUTRE_BIC = 'autre-bic';
	const AUTRE_BNC = 'autre-bnc';

	const ACCRUAL = 'accrual';

	public static function getSelection(): array {
		return FinancialYear::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): FinancialYearModel {
		if(self::$model === NULL) {
			self::$model = new FinancialYearModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('FinancialYear::'.$failName, $arguments, $wrapper);
	}

}


class FinancialYearModel extends \ModuleModel {

	protected string $module = 'account\FinancialYear';
	protected string $package = 'account';
	protected string $table = 'accountFinancialYear';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'startDate' => ['date', 'cast' => 'string'],
			'endDate' => ['date', 'cast' => 'string'],
			'status' => ['enum', [\account\FinancialYear::OPEN, \account\FinancialYear::CLOSE], 'cast' => 'enum'],
			'hasVat' => ['bool', 'cast' => 'bool'],
			'vatFrequency' => ['enum', [\account\FinancialYear::MONTHLY, \account\FinancialYear::QUARTERLY, \account\FinancialYear::ANNUALLY], 'null' => TRUE, 'cast' => 'enum'],
			'vatChargeability' => ['enum', [\account\FinancialYear::CASH, \account\FinancialYear::DEBIT], 'null' => TRUE, 'cast' => 'enum'],
			'taxSystem' => ['enum', [\account\FinancialYear::MICRO_BA, \account\FinancialYear::BA_REEL_SIMPLIFIE, \account\FinancialYear::BA_REEL_NORMAL, \account\FinancialYear::AUTRE_BIC, \account\FinancialYear::AUTRE_BNC], 'cast' => 'enum'],
			'accountingType' => ['enum', [\account\FinancialYear::ACCRUAL, \account\FinancialYear::CASH], 'cast' => 'enum'],
			'legalCategory' => ['int16', 'min' => 1000, 'max' => 9999, 'null' => TRUE, 'cast' => 'int'],
			'associates' => ['int8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'openDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'closeDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'startDate', 'endDate', 'status', 'hasVat', 'vatFrequency', 'vatChargeability', 'taxSystem', 'accountingType', 'legalCategory', 'associates', 'openDate', 'closeDate', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'createdBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return FinancialYear::OPEN;

			case 'accountingType' :
				return FinancialYear::CASH;

			case 'createdAt' :
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

			case 'vatFrequency' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'vatChargeability' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'taxSystem' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'accountingType' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): FinancialYearModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FinancialYearModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): FinancialYearModel {
		return $this->where('id', ...$data);
	}

	public function whereStartDate(...$data): FinancialYearModel {
		return $this->where('startDate', ...$data);
	}

	public function whereEndDate(...$data): FinancialYearModel {
		return $this->where('endDate', ...$data);
	}

	public function whereStatus(...$data): FinancialYearModel {
		return $this->where('status', ...$data);
	}

	public function whereHasVat(...$data): FinancialYearModel {
		return $this->where('hasVat', ...$data);
	}

	public function whereVatFrequency(...$data): FinancialYearModel {
		return $this->where('vatFrequency', ...$data);
	}

	public function whereVatChargeability(...$data): FinancialYearModel {
		return $this->where('vatChargeability', ...$data);
	}

	public function whereTaxSystem(...$data): FinancialYearModel {
		return $this->where('taxSystem', ...$data);
	}

	public function whereAccountingType(...$data): FinancialYearModel {
		return $this->where('accountingType', ...$data);
	}

	public function whereLegalCategory(...$data): FinancialYearModel {
		return $this->where('legalCategory', ...$data);
	}

	public function whereAssociates(...$data): FinancialYearModel {
		return $this->where('associates', ...$data);
	}

	public function whereOpenDate(...$data): FinancialYearModel {
		return $this->where('openDate', ...$data);
	}

	public function whereCloseDate(...$data): FinancialYearModel {
		return $this->where('closeDate', ...$data);
	}

	public function whereCreatedAt(...$data): FinancialYearModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): FinancialYearModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class FinancialYearCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): FinancialYear {

		$e = new FinancialYear();

		if(empty($id)) {
			FinancialYear::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = FinancialYear::getSelection();
		}

		if(FinancialYear::model()
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
			$properties = FinancialYear::getSelection();
		}

		if($sort !== NULL) {
			FinancialYear::model()->sort($sort);
		}

		return FinancialYear::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): FinancialYear {

		return new FinancialYear(['id' => NULL]);

	}

	public static function create(FinancialYear $e): void {

		FinancialYear::model()->insert($e);

	}

	public static function update(FinancialYear $e, array $properties): void {

		$e->expects(['id']);

		FinancialYear::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, FinancialYear $e, array $properties): void {

		FinancialYear::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(FinancialYear $e): void {

		$e->expects(['id']);

		FinancialYear::model()->delete($e);

	}

}


class FinancialYearPage extends \ModulePage {

	protected string $module = 'account\FinancialYear';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FinancialYearLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FinancialYearLib::getPropertiesUpdate()
		);
	}

}
?>