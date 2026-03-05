<?php
namespace receipts;

abstract class LineElement extends \Element {

	use \FilterElement;

	private static ?LineModel $model = NULL;

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
		return Line::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): LineModel {
		if(self::$model === NULL) {
			self::$model = new LineModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Line::'.$failName, $arguments, $wrapper);
	}

}


class LineModel extends \ModuleModel {

	protected string $module = 'receipts\Line';
	protected string $package = 'receipts';
	protected string $table = 'receiptsLine';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'book' => ['element32', 'receipts\Book', 'cast' => 'element'],
			'date' => ['date', 'cast' => 'string'],
			'balance' => ['decimal', 'digits' => 10, 'decimal' => 2, 'min' => -99999999.99, 'max' => 99999999.99, 'null' => TRUE, 'cast' => 'float'],
			'amountIncludingVat' => ['decimal', 'digits' => 10, 'decimal' => 2, 'min' => 0.0, 'max' => 99999999.99, 'cast' => 'float'],
			'amountExcludingVat' => ['decimal', 'digits' => 10, 'decimal' => 2, 'min' => 0.0, 'max' => 99999999.99, 'null' => TRUE, 'cast' => 'float'],
			'position' => ['int32', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'type' => ['enum', [\receipts\Line::DEBIT, \receipts\Line::CREDIT], 'cast' => 'enum'],
			'hasVat' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'vat' => ['decimal', 'digits' => 10, 'decimal' => 2, 'min' => 0.0, 'max' => 99999999.99, 'null' => TRUE, 'cast' => 'float'],
			'vatRate' => ['decimal', 'digits' => 5, 'decimal' => 2, 'min' => -999.99, 'max' => 999.99, 'null' => TRUE, 'cast' => 'float'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'source' => ['enum', [\receipts\Line::BANK_MANUAL, \receipts\Line::BANK_CASHFLOW, \receipts\Line::PRIVATE, \receipts\Line::OTHER, \receipts\Line::INITIAL, \receipts\Line::BALANCE, \receipts\Line::BUY_MANUAL, \receipts\Line::SELL_MANUAL, \receipts\Line::SELL_INVOICE, \receipts\Line::SELL_SALE], 'cast' => 'enum'],
			'cashflow' => ['element32', 'bank\Cashflow', 'null' => TRUE, 'cast' => 'element'],
			'invoice' => ['element32', 'selling\Invoice', 'null' => TRUE, 'cast' => 'element'],
			'sale' => ['element32', 'selling\Sale', 'null' => TRUE, 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'payment' => ['element32', 'selling\Payment', 'null' => TRUE, 'cast' => 'element'],
			'account' => ['element32', 'account\Account', 'null' => TRUE, 'cast' => 'element'],
			'financialYear' => ['element32', 'account\FinancialYear', 'null' => TRUE, 'cast' => 'element'],
			'accountingHash' => ['textFixed', 'min' => 20, 'max' => 20, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'accountingReady' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'status' => ['enum', [\receipts\Line::DRAFT, \receipts\Line::VALID, \receipts\Line::DELETED], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'book', 'date', 'balance', 'amountIncludingVat', 'amountExcludingVat', 'position', 'type', 'hasVat', 'vat', 'vatRate', 'description', 'source', 'cashflow', 'invoice', 'sale', 'customer', 'payment', 'account', 'financialYear', 'accountingHash', 'accountingReady', 'status', 'createdAt'
		]);

		$this->propertiesToModule += [
			'book' => 'receipts\Book',
			'cashflow' => 'bank\Cashflow',
			'invoice' => 'selling\Invoice',
			'sale' => 'selling\Sale',
			'customer' => 'selling\Customer',
			'payment' => 'selling\Payment',
			'account' => 'account\Account',
			'financialYear' => 'account\FinancialYear',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['book'],
			['sale'],
			['invoice'],
			['payment']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'accountingReady' :
				return FALSE;

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

	public function select(...$fields): LineModel {
		return parent::select(...$fields);
	}

	public function where(...$data): LineModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): LineModel {
		return $this->where('id', ...$data);
	}

	public function whereBook(...$data): LineModel {
		return $this->where('book', ...$data);
	}

	public function whereDate(...$data): LineModel {
		return $this->where('date', ...$data);
	}

	public function whereBalance(...$data): LineModel {
		return $this->where('balance', ...$data);
	}

	public function whereAmountIncludingVat(...$data): LineModel {
		return $this->where('amountIncludingVat', ...$data);
	}

	public function whereAmountExcludingVat(...$data): LineModel {
		return $this->where('amountExcludingVat', ...$data);
	}

	public function wherePosition(...$data): LineModel {
		return $this->where('position', ...$data);
	}

	public function whereType(...$data): LineModel {
		return $this->where('type', ...$data);
	}

	public function whereHasVat(...$data): LineModel {
		return $this->where('hasVat', ...$data);
	}

	public function whereVat(...$data): LineModel {
		return $this->where('vat', ...$data);
	}

	public function whereVatRate(...$data): LineModel {
		return $this->where('vatRate', ...$data);
	}

	public function whereDescription(...$data): LineModel {
		return $this->where('description', ...$data);
	}

	public function whereSource(...$data): LineModel {
		return $this->where('source', ...$data);
	}

	public function whereCashflow(...$data): LineModel {
		return $this->where('cashflow', ...$data);
	}

	public function whereInvoice(...$data): LineModel {
		return $this->where('invoice', ...$data);
	}

	public function whereSale(...$data): LineModel {
		return $this->where('sale', ...$data);
	}

	public function whereCustomer(...$data): LineModel {
		return $this->where('customer', ...$data);
	}

	public function wherePayment(...$data): LineModel {
		return $this->where('payment', ...$data);
	}

	public function whereAccount(...$data): LineModel {
		return $this->where('account', ...$data);
	}

	public function whereFinancialYear(...$data): LineModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereAccountingHash(...$data): LineModel {
		return $this->where('accountingHash', ...$data);
	}

	public function whereAccountingReady(...$data): LineModel {
		return $this->where('accountingReady', ...$data);
	}

	public function whereStatus(...$data): LineModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): LineModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class LineCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Line {

		$e = new Line();

		if(empty($id)) {
			Line::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Line::getSelection();
		}

		if(Line::model()
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
			$properties = Line::getSelection();
		}

		if($sort !== NULL) {
			Line::model()->sort($sort);
		}

		return Line::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Line {

		return new Line($properties);

	}

	public static function create(Line $e): void {

		Line::model()->insert($e);

	}

	public static function update(Line $e, array $properties): void {

		$e->expects(['id']);

		Line::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Line $e, array $properties): void {

		Line::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Line $e): void {

		$e->expects(['id']);

		Line::model()->delete($e);

	}

}


class LinePage extends \ModulePage {

	protected string $module = 'receipts\Line';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? LineLib::getPropertiesCreate(),
		   $propertiesUpdate ?? LineLib::getPropertiesUpdate()
		);
	}

}
?>