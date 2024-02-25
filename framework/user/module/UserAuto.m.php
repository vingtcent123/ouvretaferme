<?php
namespace user;

abstract class UserAutoElement extends \Element {

	use \FilterElement;

	private static ?UserAutoModel $model = NULL;

	const ACTIVE = 'active';
	const DELETED = 'deleted';

	public static function getSelection(): array {
		return UserAuto::model()->getProperties();
	}

	public static function model(): UserAutoModel {
		if(self::$model === NULL) {
			self::$model = new UserAutoModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('UserAuto::'.$failName, $arguments, $wrapper);
	}

}


class UserAutoModel extends \ModuleModel {

	protected string $module = 'user\UserAuto';
	protected string $package = 'user';
	protected string $table = 'userUserAuto';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'key' => ['text8', 'cast' => 'string'],
			'usedAt' => ['datetime', 'cast' => 'string'],
			'expiresAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\user\UserAuto::ACTIVE, \user\UserAuto::DELETED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'key', 'usedAt', 'expiresAt', 'status'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'usedAt' :
				return new \Sql('NOW()');

			case 'status' :
				return UserAuto::ACTIVE;

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

	public function select(...$fields): UserAutoModel {
		return parent::select(...$fields);
	}

	public function where(...$data): UserAutoModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): UserAutoModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): UserAutoModel {
		return $this->where('user', ...$data);
	}

	public function whereKey(...$data): UserAutoModel {
		return $this->where('key', ...$data);
	}

	public function whereUsedAt(...$data): UserAutoModel {
		return $this->where('usedAt', ...$data);
	}

	public function whereExpiresAt(...$data): UserAutoModel {
		return $this->where('expiresAt', ...$data);
	}

	public function whereStatus(...$data): UserAutoModel {
		return $this->where('status', ...$data);
	}


}


abstract class UserAutoCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): UserAuto {

		$e = new UserAuto();

		if(empty($id)) {
			UserAuto::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = UserAuto::getSelection();
		}

		if(UserAuto::model()
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
			$properties = UserAuto::getSelection();
		}

		if($sort !== NULL) {
			UserAuto::model()->sort($sort);
		}

		return UserAuto::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): UserAuto {

		return new UserAuto(['id' => NULL]);

	}

	public static function create(UserAuto $e): void {

		UserAuto::model()->insert($e);

	}

	public static function update(UserAuto $e, array $properties): void {

		$e->expects(['id']);

		UserAuto::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, UserAuto $e, array $properties): void {

		UserAuto::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(UserAuto $e): void {

		$e->expects(['id']);

		UserAuto::model()->delete($e);

	}

}


class UserAutoPage extends \ModulePage {

	protected string $module = 'user\UserAuto';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? UserAutoLib::getPropertiesCreate(),
		   $propertiesUpdate ?? UserAutoLib::getPropertiesUpdate()
		);
	}

}
?>