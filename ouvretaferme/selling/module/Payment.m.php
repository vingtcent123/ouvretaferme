<?php
namespace selling;

abstract class PaymentElement extends \Element {

	use \FilterElement;

	private static ?PaymentModel $model = NULL;

	const PENDING = 'pending';
	const SUCCESS = 'success';
	const FAILURE = 'failure';

	public static function getSelection(): array {
		return Payment::model()->getProperties();
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
			'sale' => ['element32', 'selling\Sale', 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'checkoutId' => ['text8', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'paymentIntentId' => ['text8', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\selling\Payment::PENDING, \selling\Payment::SUCCESS, \selling\Payment::FAILURE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'sale', 'customer', 'farm', 'checkoutId', 'paymentIntentId', 'status'
		]);

		$this->propertiesToModule += [
			'sale' => 'selling\Sale',
			'customer' => 'selling\Customer',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['sale']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['checkoutId'],
			['paymentIntentId']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Payment::PENDING;

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

	public function select(...$fields): PaymentModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PaymentModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PaymentModel {
		return $this->where('id', ...$data);
	}

	public function whereSale(...$data): PaymentModel {
		return $this->where('sale', ...$data);
	}

	public function whereCustomer(...$data): PaymentModel {
		return $this->where('customer', ...$data);
	}

	public function whereFarm(...$data): PaymentModel {
		return $this->where('farm', ...$data);
	}

	public function whereCheckoutId(...$data): PaymentModel {
		return $this->where('checkoutId', ...$data);
	}

	public function wherePaymentIntentId(...$data): PaymentModel {
		return $this->where('paymentIntentId', ...$data);
	}

	public function whereStatus(...$data): PaymentModel {
		return $this->where('status', ...$data);
	}


}


abstract class PaymentCrud extends \ModuleCrud {

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

	public static function getCreateElement(): Payment {

		return new Payment(['id' => NULL]);

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