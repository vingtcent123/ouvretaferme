<?php
namespace user;

abstract class RoleElement extends \Element {

	use \FilterElement;

	private static ?RoleModel $model = NULL;

	public static function getSelection(): array {
		return Role::model()->getProperties();
	}

	public static function model(): RoleModel {
		if(self::$model === NULL) {
			self::$model = new RoleModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Role::'.$failName, $arguments, $wrapper);
	}

}


class RoleModel extends \ModuleModel {

	protected string $module = 'user\Role';
	protected string $package = 'user';
	protected string $table = 'userRole';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'fqn' => ['fqn', 'unique' => TRUE, 'cast' => 'string'],
			'name' => ['text8', 'cast' => 'string'],
			'emoji' => ['textFixed', 'min' => 1, 'max' => 3, 'null' => TRUE, 'cast' => 'string'],
			'level' => ['int8', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'can' => ['json', 'null' => TRUE, 'cast' => 'array'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'fqn', 'name', 'emoji', 'level', 'can'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fqn']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'can' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'can' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): RoleModel {
		return parent::select(...$fields);
	}

	public function where(...$data): RoleModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): RoleModel {
		return $this->where('id', ...$data);
	}

	public function whereFqn(...$data): RoleModel {
		return $this->where('fqn', ...$data);
	}

	public function whereName(...$data): RoleModel {
		return $this->where('name', ...$data);
	}

	public function whereEmoji(...$data): RoleModel {
		return $this->where('emoji', ...$data);
	}

	public function whereLevel(...$data): RoleModel {
		return $this->where('level', ...$data);
	}

	public function whereCan(...$data): RoleModel {
		return $this->where('can', ...$data);
	}


}


abstract class RoleCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Role {

		$e = new Role();

		if(empty($id)) {
			Role::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Role::getSelection();
		}

		if(Role::model()
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
			$properties = Role::getSelection();
		}

		if($sort !== NULL) {
			Role::model()->sort($sort);
		}

		return Role::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Role {

		$e = new Role();

		if(empty($fqn)) {
			Role::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Role::getSelection();
		}

		if(Role::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Role::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Role::getSelection();
		}

		return Role::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCreateElement(): Role {

		return new Role(['id' => NULL]);

	}

	public static function create(Role $e): void {

		Role::model()->insert($e);

	}

	public static function update(Role $e, array $properties): void {

		$e->expects(['id']);

		Role::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Role $e, array $properties): void {

		Role::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Role $e): void {

		$e->expects(['id']);

		Role::model()->delete($e);

	}

}


class RolePage extends \ModulePage {

	protected string $module = 'user\Role';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? RoleLib::getPropertiesCreate(),
		   $propertiesUpdate ?? RoleLib::getPropertiesUpdate()
		);
	}

}
?>