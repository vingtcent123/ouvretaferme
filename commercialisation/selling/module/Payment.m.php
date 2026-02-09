<?php
namespace selling;

abstract class PaymentElement extends \Element {

	use \FilterElement;

	private static ?PaymentModel $model = NULL;

	const SALE = 'sale';
	const INVOICE = 'invoice';

	const NOT_PAID = 'not-paid';
	const PAID = 'paid';
	const FAILED = 'failed';

	const WAITING = 'waiting';
	const DRAFT = 'draft';
	const VALID = 'valid';
	const IGNORED = 'ignored';

	public static function getSelection(): array {
		return Payment::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): PaymentModel {
		if(self::$model === NULL) {
			self::$model = new PaymentModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Payment::'.$failName, $arguments, $wrapper);
	}

}


class PaymentModel extends \ModuleModel {

	protected string $module = 'selling\Payment';
	protected string $package = 'selling';
	protected string $table = 'sellingPayment';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'source' => ['enum', [\selling\Payment::SALE, \selling\Payment::INVOICE], 'cast' => 'enum'],
			'sale' => ['element32', 'selling\Sale', 'null' => TRUE, 'cast' => 'element'],
			'invoice' => ['element32', 'selling\Invoice', 'null' => TRUE, 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'amountIncludingVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'method' => ['element32', 'payment\Method', 'cast' => 'element'],
			'methodName' => ['text8', 'cast' => 'string'],
			'onlineCheckoutId' => ['text8', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'onlinePaymentIntentId' => ['text8', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\selling\Payment::NOT_PAID, \selling\Payment::PAID, \selling\Payment::FAILED], 'cast' => 'enum'],
			'statusCash' => ['enum', [\selling\Payment::WAITING, \selling\Payment::DRAFT, \selling\Payment::VALID, \selling\Payment::IGNORED], 'cast' => 'enum'],
			'paidAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'accountingHash' => ['textFixed', 'min' => 20, 'max' => 20, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'closed' => ['bool', 'cast' => 'bool'],
			'closedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'source', 'sale', 'invoice', 'customer', 'farm', 'amountIncludingVat', 'method', 'methodName', 'onlineCheckoutId', 'onlinePaymentIntentId', 'status', 'statusCash', 'paidAt', 'accountingHash', 'createdAt', 'closed', 'closedAt'
		]);

		$this->propertiesToModule += [
			'sale' => 'selling\Sale',
			'invoice' => 'selling\Invoice',
			'customer' => 'selling\Customer',
			'farm' => 'farm\Farm',
			'method' => 'payment\Method',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['sale'],
			['invoice']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['onlineCheckoutId'],
			['onlinePaymentIntentId']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'statusCash' :
				return Payment::WAITING;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'closed' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'source' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'statusCash' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): PaymentModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PaymentModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PaymentModel {
		return $this->where('id', ...$data);
	}

	public function whereSource(...$data): PaymentModel {
		return $this->where('source', ...$data);
	}

	public function whereSale(...$data): PaymentModel {
		return $this->where('sale', ...$data);
	}

	public function whereInvoice(...$data): PaymentModel {
		return $this->where('invoice', ...$data);
	}

	public function whereCustomer(...$data): PaymentModel {
		return $this->where('customer', ...$data);
	}

	public function whereFarm(...$data): PaymentModel {
		return $this->where('farm', ...$data);
	}

	public function whereAmountIncludingVat(...$data): PaymentModel {
		return $this->where('amountIncludingVat', ...$data);
	}

	public function whereMethod(...$data): PaymentModel {
		return $this->where('method', ...$data);
	}

	public function whereMethodName(...$data): PaymentModel {
		return $this->where('methodName', ...$data);
	}

	public function whereOnlineCheckoutId(...$data): PaymentModel {
		return $this->where('onlineCheckoutId', ...$data);
	}

	public function whereOnlinePaymentIntentId(...$data): PaymentModel {
		return $this->where('onlinePaymentIntentId', ...$data);
	}

	public function whereStatus(...$data): PaymentModel {
		return $this->where('status', ...$data);
	}

	public function whereStatusCash(...$data): PaymentModel {
		return $this->where('statusCash', ...$data);
	}

	public function wherePaidAt(...$data): PaymentModel {
		return $this->where('paidAt', ...$data);
	}

	public function whereAccountingHash(...$data): PaymentModel {
		return $this->where('accountingHash', ...$data);
	}

	public function whereCreatedAt(...$data): PaymentModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereClosed(...$data): PaymentModel {
		return $this->where('closed', ...$data);
	}

	public function whereClosedAt(...$data): PaymentModel {
		return $this->where('closedAt', ...$data);
	}


}


abstract class PaymentCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Payment {

		$e = new Payment();

		if(empty($id)) {
			Payment::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Payment::getSelection();
		}

		if(Payment::model()
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
			$properties = Payment::getSelection();
		}

		if($sort !== NULL) {
			Payment::model()->sort($sort);
		}

		return Payment::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Payment {

		return new Payment($properties);

	}

	public static function create(Payment $e): void {

		Payment::model()->insert($e);

	}

	public static function update(Payment $e, array $properties): void {

		$e->expects(['id']);

		Payment::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Payment $e, array $properties): void {

		Payment::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Payment $e): void {

		$e->expects(['id']);

		Payment::model()->delete($e);

	}

}


class PaymentPage extends \ModulePage {

	protected string $module = 'selling\Payment';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PaymentLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PaymentLib::getPropertiesUpdate()
		);
	}

}
?>