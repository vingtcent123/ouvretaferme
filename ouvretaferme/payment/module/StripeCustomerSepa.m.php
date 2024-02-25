<?php
namespace payment;

abstract class StripeCustomerSepaElement extends \Element {

	use \FilterElement;

	private static ?StripeCustomerSepaModel $model = NULL;

	const CONFIGURING = 'configuring';
	const FAILED = 'failed';
	const VALID = 'valid';

	public static function getSelection(): array {
		return StripeCustomerSepa::model()->getProperties();
	}

	public static function model(): StripeCustomerSepaModel {
		if(self::$model === NULL) {
			self::$model = new StripeCustomerSepaModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('StripeCustomerSepa::'.$failName, $arguments, $wrapper);
	}

}


class StripeCustomerSepaModel extends \ModuleModel {

	protected string $module = 'payment\StripeCustomerSepa';
	protected string $package = 'payment';
	protected string $table = 'paymentStripeCustomerSepa';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'customer' => ['element32', 'selling\Customer', 'unique' => TRUE, 'cast' => 'element'],
			'stripeCustomerId' => ['text8', 'cast' => 'string'],
			'stripeSessionIntentId' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'stripePaymentMethodId' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\payment\StripeCustomerSepa::CONFIGURING, \payment\StripeCustomerSepa::FAILED, \payment\StripeCustomerSepa::VALID], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'customer', 'stripeCustomerId', 'stripeSessionIntentId', 'stripePaymentMethodId', 'status'
		]);

		$this->propertiesToModule += [
			'customer' => 'selling\Customer',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['customer']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return StripeCustomerSepa::CONFIGURING;

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

	public function select(...$fields): StripeCustomerSepaModel {
		return parent::select(...$fields);
	}

	public function where(...$data): StripeCustomerSepaModel {
		return parent::where(...$data);
	}

	public function whereCustomer(...$data): StripeCustomerSepaModel {
		return $this->where('customer', ...$data);
	}

	public function whereStripeCustomerId(...$data): StripeCustomerSepaModel {
		return $this->where('stripeCustomerId', ...$data);
	}

	public function whereStripeSessionIntentId(...$data): StripeCustomerSepaModel {
		return $this->where('stripeSessionIntentId', ...$data);
	}

	public function whereStripePaymentMethodId(...$data): StripeCustomerSepaModel {
		return $this->where('stripePaymentMethodId', ...$data);
	}

	public function whereStatus(...$data): StripeCustomerSepaModel {
		return $this->where('status', ...$data);
	}


}


abstract class StripeCustomerSepaCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): StripeCustomerSepa {

		$e = new StripeCustomerSepa();

		if(empty($id)) {
			StripeCustomerSepa::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = StripeCustomerSepa::getSelection();
		}

		if(StripeCustomerSepa::model()
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
			$properties = StripeCustomerSepa::getSelection();
		}

		if($sort !== NULL) {
			StripeCustomerSepa::model()->sort($sort);
		}

		return StripeCustomerSepa::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): StripeCustomerSepa {

		return new StripeCustomerSepa(['id' => NULL]);

	}

	public static function create(StripeCustomerSepa $e): void {

		StripeCustomerSepa::model()->insert($e);

	}

	public static function update(StripeCustomerSepa $e, array $properties): void {

		$e->expects(['id']);

		StripeCustomerSepa::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, StripeCustomerSepa $e, array $properties): void {

		StripeCustomerSepa::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(StripeCustomerSepa $e): void {

		$e->expects(['id']);

		StripeCustomerSepa::model()->delete($e);

	}

}


class StripeCustomerSepaPage extends \ModulePage {

	protected string $module = 'payment\StripeCustomerSepa';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? StripeCustomerSepaLib::getPropertiesCreate(),
		   $propertiesUpdate ?? StripeCustomerSepaLib::getPropertiesUpdate()
		);
	}

}
?>