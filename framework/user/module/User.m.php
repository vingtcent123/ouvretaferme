<?php
namespace user;

abstract class UserElement extends \Element {

	use \FilterElement;

	private static ?UserModel $model = NULL;

	const PRIVATE = 'private';
	const PRO = 'pro';

	const PUBLIC = 'public';

	const ACTIVE = 'active';
	const SUSPENDED = 'suspended';
	const CLOSED = 'closed';

	public static function getSelection(): array {
		return User::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): UserModel {
		if(self::$model === NULL) {
			self::$model = new UserModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('User::'.$failName, $arguments, $wrapper);
	}

}


class UserModel extends \ModuleModel {

	protected string $module = 'user\User';
	protected string $package = 'user';
	protected string $table = 'user';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'type' => ['enum', [\user\User::PRIVATE, \user\User::PRO], 'cast' => 'enum'],
			'firstName' => ['text8', 'min' => 1, 'max' => \user\UserSetting::NAME_SIZE_MAX, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'lastName' => ['text8', 'min' => 1, 'max' => \user\UserSetting::NAME_SIZE_MAX, 'collate' => 'general', 'cast' => 'string'],
			'legalName' => ['text8', 'min' => 1, 'max' => \user\UserSetting::NAME_SIZE_MAX, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'email' => ['email', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'phone' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'siret' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'invoiceCountry' => ['element32', 'user\Country', 'null' => TRUE, 'cast' => 'element'],
			'invoiceStreet1' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'invoiceStreet2' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'invoicePostcode' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'invoiceCity' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'deliveryCountry' => ['element32', 'user\Country', 'null' => TRUE, 'cast' => 'element'],
			'deliveryStreet1' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'deliveryStreet2' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'deliveryPostcode' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'deliveryCity' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'verified' => ['bool', 'cast' => 'bool'],
			'visibility' => ['enum', [\user\User::PUBLIC, \user\User::PRIVATE], 'cast' => 'enum'],
			'status' => ['enum', [\user\User::ACTIVE, \user\User::SUSPENDED, \user\User::CLOSED], 'cast' => 'enum'],
			'referer' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'seen' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'seniority' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'role' => ['element32', 'user\Role', 'null' => TRUE, 'cast' => 'element'],
			'vignette' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'onlineToday' => ['bool', 'cast' => 'bool'],
			'loggedAt' => ['datetime', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'ping' => ['datetime', 'cast' => 'string'],
			'deletedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'bounce' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'type', 'firstName', 'lastName', 'legalName', 'email', 'phone', 'siret', 'invoiceCountry', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'deliveryCountry', 'deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'verified', 'visibility', 'status', 'referer', 'seen', 'seniority', 'role', 'vignette', 'onlineToday', 'loggedAt', 'createdAt', 'ping', 'deletedAt', 'bounce'
		]);

		$this->propertiesToModule += [
			'invoiceCountry' => 'user\Country',
			'deliveryCountry' => 'user\Country',
			'referer' => 'user\User',
			'role' => 'user\Role',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['referer']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['email']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'type' :
				return User::PRIVATE;

			case 'verified' :
				return FALSE;

			case 'visibility' :
				return User::PUBLIC;

			case 'status' :
				return User::ACTIVE;

			case 'seen' :
				return 0;

			case 'seniority' :
				return 1;

			case 'onlineToday' :
				return FALSE;

			case 'loggedAt' :
				return new \Sql('NOW()');

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'ping' :
				return new \Sql('NOW()');

			case 'bounce' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'visibility' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): UserModel {
		return parent::select(...$fields);
	}

	public function where(...$data): UserModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): UserModel {
		return $this->where('id', ...$data);
	}

	public function whereType(...$data): UserModel {
		return $this->where('type', ...$data);
	}

	public function whereFirstName(...$data): UserModel {
		return $this->where('firstName', ...$data);
	}

	public function whereLastName(...$data): UserModel {
		return $this->where('lastName', ...$data);
	}

	public function whereLegalName(...$data): UserModel {
		return $this->where('legalName', ...$data);
	}

	public function whereEmail(...$data): UserModel {
		return $this->where('email', ...$data);
	}

	public function wherePhone(...$data): UserModel {
		return $this->where('phone', ...$data);
	}

	public function whereSiret(...$data): UserModel {
		return $this->where('siret', ...$data);
	}

	public function whereInvoiceCountry(...$data): UserModel {
		return $this->where('invoiceCountry', ...$data);
	}

	public function whereInvoiceStreet1(...$data): UserModel {
		return $this->where('invoiceStreet1', ...$data);
	}

	public function whereInvoiceStreet2(...$data): UserModel {
		return $this->where('invoiceStreet2', ...$data);
	}

	public function whereInvoicePostcode(...$data): UserModel {
		return $this->where('invoicePostcode', ...$data);
	}

	public function whereInvoiceCity(...$data): UserModel {
		return $this->where('invoiceCity', ...$data);
	}

	public function whereDeliveryCountry(...$data): UserModel {
		return $this->where('deliveryCountry', ...$data);
	}

	public function whereDeliveryStreet1(...$data): UserModel {
		return $this->where('deliveryStreet1', ...$data);
	}

	public function whereDeliveryStreet2(...$data): UserModel {
		return $this->where('deliveryStreet2', ...$data);
	}

	public function whereDeliveryPostcode(...$data): UserModel {
		return $this->where('deliveryPostcode', ...$data);
	}

	public function whereDeliveryCity(...$data): UserModel {
		return $this->where('deliveryCity', ...$data);
	}

	public function whereVerified(...$data): UserModel {
		return $this->where('verified', ...$data);
	}

	public function whereVisibility(...$data): UserModel {
		return $this->where('visibility', ...$data);
	}

	public function whereStatus(...$data): UserModel {
		return $this->where('status', ...$data);
	}

	public function whereReferer(...$data): UserModel {
		return $this->where('referer', ...$data);
	}

	public function whereSeen(...$data): UserModel {
		return $this->where('seen', ...$data);
	}

	public function whereSeniority(...$data): UserModel {
		return $this->where('seniority', ...$data);
	}

	public function whereRole(...$data): UserModel {
		return $this->where('role', ...$data);
	}

	public function whereVignette(...$data): UserModel {
		return $this->where('vignette', ...$data);
	}

	public function whereOnlineToday(...$data): UserModel {
		return $this->where('onlineToday', ...$data);
	}

	public function whereLoggedAt(...$data): UserModel {
		return $this->where('loggedAt', ...$data);
	}

	public function whereCreatedAt(...$data): UserModel {
		return $this->where('createdAt', ...$data);
	}

	public function wherePing(...$data): UserModel {
		return $this->where('ping', ...$data);
	}

	public function whereDeletedAt(...$data): UserModel {
		return $this->where('deletedAt', ...$data);
	}

	public function whereBounce(...$data): UserModel {
		return $this->where('bounce', ...$data);
	}


}


abstract class UserCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): User {

		$e = new User();

		if(empty($id)) {
			User::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = User::getSelection();
		}

		if(User::model()
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
			$properties = User::getSelection();
		}

		if($sort !== NULL) {
			User::model()->sort($sort);
		}

		return User::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): User {

		return new User(['id' => NULL]);

	}

	public static function create(User $e): void {

		User::model()->insert($e);

	}

	public static function update(User $e, array $properties): void {

		$e->expects(['id']);

		User::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, User $e, array $properties): void {

		User::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(User $e): void {

		$e->expects(['id']);

		User::model()->delete($e);

	}

}


class UserPage extends \ModulePage {

	protected string $module = 'user\User';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? UserLib::getPropertiesCreate(),
		   $propertiesUpdate ?? UserLib::getPropertiesUpdate()
		);
	}

}
?>