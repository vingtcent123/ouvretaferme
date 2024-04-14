<?php
namespace selling;

abstract class CustomerElement extends \Element {

	use \FilterElement;

	private static ?CustomerModel $model = NULL;

	const PRIVATE = 'private';
	const PRO = 'pro';

	const INDIVIDUAL = 'individual';
	const COLLECTIVE = 'collective';

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

	public static function getSelection(): array {
		return Customer::model()->getProperties();
	}

	public static function model(): CustomerModel {
		if(self::$model === NULL) {
			self::$model = new CustomerModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Customer::'.$failName, $arguments, $wrapper);
	}

}


class CustomerModel extends \ModuleModel {

	protected string $module = 'selling\Customer';
	protected string $package = 'selling';
	protected string $table = 'sellingCustomer';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'legalName' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'email' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'type' => ['enum', [\selling\Customer::PRIVATE, \selling\Customer::PRO], 'cast' => 'enum'],
			'destination' => ['enum', [\selling\Customer::INDIVIDUAL, \selling\Customer::COLLECTIVE], 'null' => TRUE, 'cast' => 'enum'],
			'discount' => ['int8', 'min' => 0, 'max' => 100, 'cast' => 'int'],
			'invoiceStreet1' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceStreet2' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoicePostcode' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceCity' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryStreet1' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryStreet2' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryPostcode' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryCity' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'phone' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'color' => ['color', 'null' => TRUE, 'cast' => 'string'],
			'emailOptIn' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'emailOptOut' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\selling\Customer::ACTIVE, \selling\Customer::INACTIVE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'legalName', 'email', 'farm', 'user', 'type', 'destination', 'discount', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'phone', 'color', 'emailOptIn', 'emailOptOut', 'createdAt', 'status'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'user' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'discount' :
				return 0;

			case 'emailOptOut' :
				return TRUE;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Customer::ACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'destination' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): CustomerModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CustomerModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CustomerModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): CustomerModel {
		return $this->where('name', ...$data);
	}

	public function whereLegalName(...$data): CustomerModel {
		return $this->where('legalName', ...$data);
	}

	public function whereEmail(...$data): CustomerModel {
		return $this->where('email', ...$data);
	}

	public function whereFarm(...$data): CustomerModel {
		return $this->where('farm', ...$data);
	}

	public function whereUser(...$data): CustomerModel {
		return $this->where('user', ...$data);
	}

	public function whereType(...$data): CustomerModel {
		return $this->where('type', ...$data);
	}

	public function whereDestination(...$data): CustomerModel {
		return $this->where('destination', ...$data);
	}

	public function whereDiscount(...$data): CustomerModel {
		return $this->where('discount', ...$data);
	}

	public function whereInvoiceStreet1(...$data): CustomerModel {
		return $this->where('invoiceStreet1', ...$data);
	}

	public function whereInvoiceStreet2(...$data): CustomerModel {
		return $this->where('invoiceStreet2', ...$data);
	}

	public function whereInvoicePostcode(...$data): CustomerModel {
		return $this->where('invoicePostcode', ...$data);
	}

	public function whereInvoiceCity(...$data): CustomerModel {
		return $this->where('invoiceCity', ...$data);
	}

	public function whereDeliveryStreet1(...$data): CustomerModel {
		return $this->where('deliveryStreet1', ...$data);
	}

	public function whereDeliveryStreet2(...$data): CustomerModel {
		return $this->where('deliveryStreet2', ...$data);
	}

	public function whereDeliveryPostcode(...$data): CustomerModel {
		return $this->where('deliveryPostcode', ...$data);
	}

	public function whereDeliveryCity(...$data): CustomerModel {
		return $this->where('deliveryCity', ...$data);
	}

	public function wherePhone(...$data): CustomerModel {
		return $this->where('phone', ...$data);
	}

	public function whereColor(...$data): CustomerModel {
		return $this->where('color', ...$data);
	}

	public function whereEmailOptIn(...$data): CustomerModel {
		return $this->where('emailOptIn', ...$data);
	}

	public function whereEmailOptOut(...$data): CustomerModel {
		return $this->where('emailOptOut', ...$data);
	}

	public function whereCreatedAt(...$data): CustomerModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): CustomerModel {
		return $this->where('status', ...$data);
	}


}


abstract class CustomerCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Customer {

		$e = new Customer();

		if(empty($id)) {
			Customer::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Customer::getSelection();
		}

		if(Customer::model()
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
			$properties = Customer::getSelection();
		}

		if($sort !== NULL) {
			Customer::model()->sort($sort);
		}

		return Customer::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Customer {

		return new Customer(['id' => NULL]);

	}

	public static function create(Customer $e): void {

		Customer::model()->insert($e);

	}

	public static function update(Customer $e, array $properties): void {

		$e->expects(['id']);

		Customer::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Customer $e, array $properties): void {

		Customer::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Customer $e): void {

		$e->expects(['id']);

		Customer::model()->delete($e);

	}

}


class CustomerPage extends \ModulePage {

	protected string $module = 'selling\Customer';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CustomerLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CustomerLib::getPropertiesUpdate()
		);
	}

}
?>