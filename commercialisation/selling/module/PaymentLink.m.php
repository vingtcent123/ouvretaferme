<?php
namespace selling;

abstract class PaymentLinkElement extends \Element {

	use \FilterElement;

	private static ?PaymentLinkModel $model = NULL;

	const SALE = 'sale';
	const INVOICE = 'invoice';

	const ACTIVE = 'active';
	const PAID = 'paid';
	const INACTIVE = 'inactive';
	const EXPIRED = 'expired';

	public static function getSelection(): array {
		return PaymentLink::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): PaymentLinkModel {
		if(self::$model === NULL) {
			self::$model = new PaymentLinkModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('PaymentLink::'.$failName, $arguments, $wrapper);
	}

}


class PaymentLinkModel extends \ModuleModel {

	protected string $module = 'selling\PaymentLink';
	protected string $package = 'selling';
	protected string $table = 'sellingPaymentLink';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'source' => ['enum', [\selling\PaymentLink::SALE, \selling\PaymentLink::INVOICE], 'cast' => 'enum'],
			'sale' => ['element32', 'selling\Sale', 'null' => TRUE, 'cast' => 'element'],
			'invoice' => ['element32', 'selling\Invoice', 'null' => TRUE, 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'amountIncludingVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'paymentLinkId' => ['text8', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'url' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'validUntil' => ['date', 'cast' => 'string'],
			'status' => ['enum', [\selling\PaymentLink::ACTIVE, \selling\PaymentLink::PAID, \selling\PaymentLink::INACTIVE, \selling\PaymentLink::EXPIRED], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'paidAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'source', 'sale', 'invoice', 'customer', 'farm', 'amountIncludingVat', 'paymentLinkId', 'url', 'validUntil', 'status', 'createdAt', 'paidAt'
		]);

		$this->propertiesToModule += [
			'sale' => 'selling\Sale',
			'invoice' => 'selling\Invoice',
			'customer' => 'selling\Customer',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['sale'],
			['invoice']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['paymentLinkId']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return PaymentLink::ACTIVE;

			case 'createdAt' :
				return new \Sql('NOW()');

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

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): PaymentLinkModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PaymentLinkModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PaymentLinkModel {
		return $this->where('id', ...$data);
	}

	public function whereSource(...$data): PaymentLinkModel {
		return $this->where('source', ...$data);
	}

	public function whereSale(...$data): PaymentLinkModel {
		return $this->where('sale', ...$data);
	}

	public function whereInvoice(...$data): PaymentLinkModel {
		return $this->where('invoice', ...$data);
	}

	public function whereCustomer(...$data): PaymentLinkModel {
		return $this->where('customer', ...$data);
	}

	public function whereFarm(...$data): PaymentLinkModel {
		return $this->where('farm', ...$data);
	}

	public function whereAmountIncludingVat(...$data): PaymentLinkModel {
		return $this->where('amountIncludingVat', ...$data);
	}

	public function wherePaymentLinkId(...$data): PaymentLinkModel {
		return $this->where('paymentLinkId', ...$data);
	}

	public function whereUrl(...$data): PaymentLinkModel {
		return $this->where('url', ...$data);
	}

	public function whereValidUntil(...$data): PaymentLinkModel {
		return $this->where('validUntil', ...$data);
	}

	public function whereStatus(...$data): PaymentLinkModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): PaymentLinkModel {
		return $this->where('createdAt', ...$data);
	}

	public function wherePaidAt(...$data): PaymentLinkModel {
		return $this->where('paidAt', ...$data);
	}


}


abstract class PaymentLinkCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): PaymentLink {

		$e = new PaymentLink();

		if(empty($id)) {
			PaymentLink::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = PaymentLink::getSelection();
		}

		if(PaymentLink::model()
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
			$properties = PaymentLink::getSelection();
		}

		if($sort !== NULL) {
			PaymentLink::model()->sort($sort);
		}

		return PaymentLink::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): PaymentLink {

		return new PaymentLink($properties);

	}

	public static function create(PaymentLink $e): void {

		PaymentLink::model()->insert($e);

	}

	public static function update(PaymentLink $e, array $properties): void {

		$e->expects(['id']);

		PaymentLink::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, PaymentLink $e, array $properties): void {

		PaymentLink::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(PaymentLink $e): void {

		$e->expects(['id']);

		PaymentLink::model()->delete($e);

	}

}


class PaymentLinkPage extends \ModulePage {

	protected string $module = 'selling\PaymentLink';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PaymentLinkLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PaymentLinkLib::getPropertiesUpdate()
		);
	}

}
?>
