<?php
namespace cash;

abstract class CashElement extends \Element {

	use \FilterElement;

	private static ?CashModel $model = NULL;

	const DEBIT = 'debit';
	const CREDIT = 'credit';

	const BANK_MANUAL = 'bank-manual';
	const BANK_CASHFLOW = 'bank-cashflow';
	const PRIVATE = 'private';
	const OTHER = 'other';
	const INITIAL = 'initial';
	const BALANCE = 'balance';
	const BUY_MANUAL = 'buy-manual';
	const SELL_MANUAL = 'sell-manual';
	const SELL_INVOICE = 'sell-invoice';
	const SELL_SALE = 'sell-sale';

	const DRAFT = 'draft';
	const VALID = 'valid';
	const DELETED = 'deleted';

	public static function getSelection(): array {
		return Cash::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): CashModel {
		if(self::$model === NULL) {
			self::$model = new CashModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Cash::'.$failName, $arguments, $wrapper);
	}

}


class CashModel extends \ModuleModel {

	protected string $module = 'cash\Cash';
	protected string $package = 'cash';
	protected string $table = 'cash';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'register' => ['element32', 'cash\Register', 'cast' => 'element'],
			'date' => ['date', 'cast' => 'string'],
			'balance' => ['decimal', 'digits' => 10, 'decimal' => 2, 'min' => -99999999.99, 'max' => 99999999.99, 'null' => TRUE, 'cast' => 'float'],
			'amountIncludingVat' => ['decimal', 'digits' => 10, 'decimal' => 2, 'min' => 0.0, 'max' => 99999999.99, 'cast' => 'float'],
			'amountExcludingVat' => ['decimal', 'digits' => 10, 'decimal' => 2, 'min' => 0.0, 'max' => 99999999.99, 'null' => TRUE, 'cast' => 'float'],
			'position' => ['int32', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'type' => ['enum', [\cash\Cash::DEBIT, \cash\Cash::CREDIT], 'cast' => 'enum'],
			'hasVat' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'vat' => ['decimal', 'digits' => 10, 'decimal' => 2, 'min' => 0.0, 'max' => 99999999.99, 'null' => TRUE, 'cast' => 'float'],
			'vatRate' => ['decimal', 'digits' => 5, 'decimal' => 2, 'min' => -999.99, 'max' => 999.99, 'null' => TRUE, 'cast' => 'float'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'source' => ['enum', [\cash\Cash::BANK_MANUAL, \cash\Cash::BANK_CASHFLOW, \cash\Cash::PRIVATE, \cash\Cash::OTHER, \cash\Cash::INITIAL, \cash\Cash::BALANCE, \cash\Cash::BUY_MANUAL, \cash\Cash::SELL_MANUAL, \cash\Cash::SELL_INVOICE, \cash\Cash::SELL_SALE], 'cast' => 'enum'],
			'cashflow' => ['element32', 'bank\Cashflow', 'null' => TRUE, 'cast' => 'element'],
			'invoice' => ['element32', 'selling\Invoice', 'null' => TRUE, 'cast' => 'element'],
			'sale' => ['element32', 'selling\Sale', 'null' => TRUE, 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'payment' => ['element32', 'selling\Payment', 'null' => TRUE, 'cast' => 'element'],
			'account' => ['element32', 'account\Account', 'null' => TRUE, 'cast' => 'element'],
			'financialYear' => ['element32', 'account\FinancialYear', 'null' => TRUE, 'cast' => 'element'],
			'status' => ['enum', [\cash\Cash::DRAFT, \cash\Cash::VALID, \cash\Cash::DELETED], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'register', 'date', 'balance', 'amountIncludingVat', 'amountExcludingVat', 'position', 'type', 'hasVat', 'vat', 'vatRate', 'description', 'source', 'cashflow', 'invoice', 'sale', 'customer', 'payment', 'account', 'financialYear', 'status', 'createdAt'
		]);

		$this->propertiesToModule += [
			'register' => 'cash\Register',
			'cashflow' => 'bank\Cashflow',
			'invoice' => 'selling\Invoice',
			'sale' => 'selling\Sale',
			'customer' => 'selling\Customer',
			'payment' => 'selling\Payment',
			'account' => 'account\Account',
			'financialYear' => 'account\FinancialYear',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['register'],
			['sale'],
			['invoice'],
			['payment']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'source' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): CashModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CashModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CashModel {
		return $this->where('id', ...$data);
	}

	public function whereRegister(...$data): CashModel {
		return $this->where('register', ...$data);
	}

	public function whereDate(...$data): CashModel {
		return $this->where('date', ...$data);
	}

	public function whereBalance(...$data): CashModel {
		return $this->where('balance', ...$data);
	}

	public function whereAmountIncludingVat(...$data): CashModel {
		return $this->where('amountIncludingVat', ...$data);
	}

	public function whereAmountExcludingVat(...$data): CashModel {
		return $this->where('amountExcludingVat', ...$data);
	}

	public function wherePosition(...$data): CashModel {
		return $this->where('position', ...$data);
	}

	public function whereType(...$data): CashModel {
		return $this->where('type', ...$data);
	}

	public function whereHasVat(...$data): CashModel {
		return $this->where('hasVat', ...$data);
	}

	public function whereVat(...$data): CashModel {
		return $this->where('vat', ...$data);
	}

	public function whereVatRate(...$data): CashModel {
		return $this->where('vatRate', ...$data);
	}

	public function whereDescription(...$data): CashModel {
		return $this->where('description', ...$data);
	}

	public function whereSource(...$data): CashModel {
		return $this->where('source', ...$data);
	}

	public function whereCashflow(...$data): CashModel {
		return $this->where('cashflow', ...$data);
	}

	public function whereInvoice(...$data): CashModel {
		return $this->where('invoice', ...$data);
	}

	public function whereSale(...$data): CashModel {
		return $this->where('sale', ...$data);
	}

	public function whereCustomer(...$data): CashModel {
		return $this->where('customer', ...$data);
	}

	public function wherePayment(...$data): CashModel {
		return $this->where('payment', ...$data);
	}

	public function whereAccount(...$data): CashModel {
		return $this->where('account', ...$data);
	}

	public function whereFinancialYear(...$data): CashModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereStatus(...$data): CashModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): CashModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class CashCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Cash {

		$e = new Cash();

		if(empty($id)) {
			Cash::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Cash::getSelection();
		}

		if(Cash::model()
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
			$properties = Cash::getSelection();
		}

		if($sort !== NULL) {
			Cash::model()->sort($sort);
		}

		return Cash::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Cash {

		return new Cash($properties);

	}

	public static function create(Cash $e): void {

		Cash::model()->insert($e);

	}

	public static function update(Cash $e, array $properties): void {

		$e->expects(['id']);

		Cash::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Cash $e, array $properties): void {

		Cash::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Cash $e): void {

		$e->expects(['id']);

		Cash::model()->delete($e);

	}

}


class CashPage extends \ModulePage {

	protected string $module = 'cash\Cash';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CashLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CashLib::getPropertiesUpdate()
		);
	}

}
?>