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

	public static function resetModel(): void {
		self::$model = NULL;
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
			'name' => ['text8', 'min' => 1, 'max' => 50, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'firstName' => ['text8', 'min' => 1, 'max' => 30, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'lastName' => ['text8', 'min' => 1, 'max' => 30, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'commercialName' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'legalName' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'document' => ['int32', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'number' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'email' => ['email', 'null' => TRUE, 'cast' => 'string'],
			'phone' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'contactName' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'groups' => ['json', 'cast' => 'array'],
			'type' => ['enum', [\selling\Customer::PRIVATE, \selling\Customer::PRO], 'cast' => 'enum'],
			'destination' => ['enum', [\selling\Customer::INDIVIDUAL, \selling\Customer::COLLECTIVE], 'null' => TRUE, 'cast' => 'enum'],
			'discount' => ['int8', 'min' => 0, 'max' => 100, 'cast' => 'int'],
			'orderFormEmail' => ['email', 'null' => TRUE, 'cast' => 'string'],
			'deliveryNoteEmail' => ['email', 'null' => TRUE, 'cast' => 'string'],
			'invoiceEmail' => ['email', 'null' => TRUE, 'cast' => 'string'],
			'invoiceStreet1' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceStreet2' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoicePostcode' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceCity' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceCountry' => ['element32', 'user\Country', 'null' => TRUE, 'cast' => 'element'],
			'siret' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'vatNumber' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryStreet1' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryStreet2' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryPostcode' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryCity' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryCountry' => ['element32', 'user\Country', 'null' => TRUE, 'cast' => 'element'],
			'defaultPaymentMethod' => ['element32', 'payment\Method', 'null' => TRUE, 'cast' => 'element'],
			'color' => ['color', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\selling\Customer::ACTIVE, \selling\Customer::INACTIVE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'firstName', 'lastName', 'commercialName', 'legalName', 'document', 'number', 'email', 'phone', 'contactName', 'farm', 'user', 'groups', 'type', 'destination', 'discount', 'orderFormEmail', 'deliveryNoteEmail', 'invoiceEmail', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceCountry', 'siret', 'vatNumber', 'deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'deliveryCountry', 'defaultPaymentMethod', 'color', 'createdAt', 'status'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'user' => 'user\User',
			'invoiceCountry' => 'user\Country',
			'deliveryCountry' => 'user\Country',
			'defaultPaymentMethod' => 'payment\Method',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'email']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'groups' :
				return [];

			case 'discount' :
				return 0;

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

			case 'groups' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

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

	public function decode(string $property, $value) {

		switch($property) {

			case 'groups' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

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

	public function whereFirstName(...$data): CustomerModel {
		return $this->where('firstName', ...$data);
	}

	public function whereLastName(...$data): CustomerModel {
		return $this->where('lastName', ...$data);
	}

	public function whereCommercialName(...$data): CustomerModel {
		return $this->where('commercialName', ...$data);
	}

	public function whereLegalName(...$data): CustomerModel {
		return $this->where('legalName', ...$data);
	}

	public function whereDocument(...$data): CustomerModel {
		return $this->where('document', ...$data);
	}

	public function whereNumber(...$data): CustomerModel {
		return $this->where('number', ...$data);
	}

	public function whereEmail(...$data): CustomerModel {
		return $this->where('email', ...$data);
	}

	public function wherePhone(...$data): CustomerModel {
		return $this->where('phone', ...$data);
	}

	public function whereContactName(...$data): CustomerModel {
		return $this->where('contactName', ...$data);
	}

	public function whereFarm(...$data): CustomerModel {
		return $this->where('farm', ...$data);
	}

	public function whereUser(...$data): CustomerModel {
		return $this->where('user', ...$data);
	}

	public function whereGroups(...$data): CustomerModel {
		return $this->where('groups', ...$data);
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

	public function whereOrderFormEmail(...$data): CustomerModel {
		return $this->where('orderFormEmail', ...$data);
	}

	public function whereDeliveryNoteEmail(...$data): CustomerModel {
		return $this->where('deliveryNoteEmail', ...$data);
	}

	public function whereInvoiceEmail(...$data): CustomerModel {
		return $this->where('invoiceEmail', ...$data);
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

	public function whereInvoiceCountry(...$data): CustomerModel {
		return $this->where('invoiceCountry', ...$data);
	}

	public function whereSiret(...$data): CustomerModel {
		return $this->where('siret', ...$data);
	}

	public function whereVatNumber(...$data): CustomerModel {
		return $this->where('vatNumber', ...$data);
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

	public function whereDeliveryCountry(...$data): CustomerModel {
		return $this->where('deliveryCountry', ...$data);
	}

	public function whereDefaultPaymentMethod(...$data): CustomerModel {
		return $this->where('defaultPaymentMethod', ...$data);
	}

	public function whereColor(...$data): CustomerModel {
		return $this->where('color', ...$data);
	}

	public function whereCreatedAt(...$data): CustomerModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): CustomerModel {
		return $this->where('status', ...$data);
	}


}


abstract class CustomerCrud extends \ModuleCrud {

 private static array $cache = [];

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

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

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