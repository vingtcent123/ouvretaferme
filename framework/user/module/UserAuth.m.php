<?php
namespace user;

abstract class UserAuthElement extends \Element {

	use \FilterElement;

	private static ?UserAuthModel $model = NULL;

	const BASIC = 'basic';
	const IMAP = 'imap';

	public static function getSelection(): array {
		return UserAuth::model()->getProperties();
	}

	public static function model(): UserAuthModel {
		if(self::$model === NULL) {
			self::$model = new UserAuthModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('UserAuth::'.$failName, $arguments, $wrapper);
	}

}


class UserAuthModel extends \ModuleModel {

	protected string $module = 'user\UserAuth';
	protected string $package = 'user';
	protected string $table = 'userUserAuth';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'userArchive' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'type' => ['enum', [\user\UserAuth::BASIC, \user\UserAuth::IMAP], 'cast' => 'enum'],
			'login' => ['text8', 'min' => 0, 'max' => 100, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'loginArchive' => ['text8', 'min' => 0, 'max' => 100, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'password' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'loggedAt' => ['datetime', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'passwordHash' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'hashExpirationDate' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'userArchive', 'type', 'login', 'loginArchive', 'password', 'loggedAt', 'createdAt', 'passwordHash', 'hashExpirationDate'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'userArchive' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'type'],
			['login', 'type']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'loggedAt' :
				return new \Sql('NOW()');

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

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): UserAuthModel {
		return parent::select(...$fields);
	}

	public function where(...$data): UserAuthModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): UserAuthModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): UserAuthModel {
		return $this->where('user', ...$data);
	}

	public function whereUserArchive(...$data): UserAuthModel {
		return $this->where('userArchive', ...$data);
	}

	public function whereType(...$data): UserAuthModel {
		return $this->where('type', ...$data);
	}

	public function whereLogin(...$data): UserAuthModel {
		return $this->where('login', ...$data);
	}

	public function whereLoginArchive(...$data): UserAuthModel {
		return $this->where('loginArchive', ...$data);
	}

	public function wherePassword(...$data): UserAuthModel {
		return $this->where('password', ...$data);
	}

	public function whereLoggedAt(...$data): UserAuthModel {
		return $this->where('loggedAt', ...$data);
	}

	public function whereCreatedAt(...$data): UserAuthModel {
		return $this->where('createdAt', ...$data);
	}

	public function wherePasswordHash(...$data): UserAuthModel {
		return $this->where('passwordHash', ...$data);
	}

	public function whereHashExpirationDate(...$data): UserAuthModel {
		return $this->where('hashExpirationDate', ...$data);
	}


}


abstract class UserAuthCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): UserAuth {

		$e = new UserAuth();

		if(empty($id)) {
			UserAuth::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = UserAuth::getSelection();
		}

		if(UserAuth::model()
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
			$properties = UserAuth::getSelection();
		}

		if($sort !== NULL) {
			UserAuth::model()->sort($sort);
		}

		return UserAuth::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): UserAuth {

		return new UserAuth(['id' => NULL]);

	}

	public static function create(UserAuth $e): void {

		UserAuth::model()->insert($e);

	}

	public static function update(UserAuth $e, array $properties): void {

		$e->expects(['id']);

		UserAuth::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, UserAuth $e, array $properties): void {

		UserAuth::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(UserAuth $e): void {

		$e->expects(['id']);

		UserAuth::model()->delete($e);

	}

}


class UserAuthPage extends \ModulePage {

	protected string $module = 'user\UserAuth';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? UserAuthLib::getPropertiesCreate(),
		   $propertiesUpdate ?? UserAuthLib::getPropertiesUpdate()
		);
	}

}
?>