<?php
namespace user;

abstract class UserElement extends \Element {

	use \FilterElement;

	private static ?UserModel $model = NULL;

	const PUBLIC = 'public';
	const PRIVATE = 'private';

	const ACTIVE = 'active';
	const SUSPENDED = 'suspended';
	const CLOSED = 'closed';

	public static function getSelection(): array {
		return User::model()->getProperties();
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
			'firstName' => ['text8', 'min' => 1, 'max' => \Setting::get('user\nameSizeMax'), 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'lastName' => ['text8', 'min' => 1, 'max' => \Setting::get('user\nameSizeMax'), 'collate' => 'general', 'cast' => 'string'],
			'email' => ['email', 'collate' => 'general', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'birthdate' => ['date', 'min' => toDate('NOW - 100 YEARS'), 'max' => toDate('NOW - 10 YEARS'), 'null' => TRUE, 'cast' => 'string'],
			'phone' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'street1' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'street2' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'postcode' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'city' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'verified' => ['bool', 'cast' => 'bool'],
			'country' => ['element32', 'user\Country', 'null' => TRUE, 'cast' => 'element'],
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
			'id', 'firstName', 'lastName', 'email', 'birthdate', 'phone', 'street1', 'street2', 'postcode', 'city', 'verified', 'country', 'visibility', 'status', 'referer', 'seen', 'seniority', 'role', 'vignette', 'onlineToday', 'loggedAt', 'createdAt', 'ping', 'deletedAt', 'bounce'
		]);

		$this->propertiesToModule += [
			'country' => 'user\Country',
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

	public function whereFirstName(...$data): UserModel {
		return $this->where('firstName', ...$data);
	}

	public function whereLastName(...$data): UserModel {
		return $this->where('lastName', ...$data);
	}

	public function whereEmail(...$data): UserModel {
		return $this->where('email', ...$data);
	}

	public function whereBirthdate(...$data): UserModel {
		return $this->where('birthdate', ...$data);
	}

	public function wherePhone(...$data): UserModel {
		return $this->where('phone', ...$data);
	}

	public function whereStreet1(...$data): UserModel {
		return $this->where('street1', ...$data);
	}

	public function whereStreet2(...$data): UserModel {
		return $this->where('street2', ...$data);
	}

	public function wherePostcode(...$data): UserModel {
		return $this->where('postcode', ...$data);
	}

	public function whereCity(...$data): UserModel {
		return $this->where('city', ...$data);
	}

	public function whereVerified(...$data): UserModel {
		return $this->where('verified', ...$data);
	}

	public function whereCountry(...$data): UserModel {
		return $this->where('country', ...$data);
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