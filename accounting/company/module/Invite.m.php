<?php
namespace company;

abstract class InviteElement extends \Element {

	use \FilterElement;

	private static ?InviteModel $model = NULL;

	const PENDING = 'pending';
	const ACCEPTED = 'accepted';

	public static function getSelection(): array {
		return Invite::model()->getProperties();
	}

	public static function model(): InviteModel {
		if(self::$model === NULL) {
			self::$model = new InviteModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Invite::'.$failName, $arguments, $wrapper);
	}

}


class InviteModel extends \ModuleModel {

	protected string $module = 'company\Invite';
	protected string $package = 'company';
	protected string $table = 'companyInvite';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'company' => ['element32', 'company\Company', 'cast' => 'element'],
			'email' => ['email', 'cast' => 'string'],
			'employee' => ['element32', 'company\Employee', 'null' => TRUE, 'cast' => 'element'],
			'expiresAt' => ['date', 'cast' => 'string'],
			'key' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\company\Invite::PENDING, \company\Invite::ACCEPTED], 'cast' => 'enum'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'company', 'email', 'employee', 'expiresAt', 'key', 'status', 'createdBy'
		]);

		$this->propertiesToModule += [
			'company' => 'company\Company',
			'employee' => 'company\Employee',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['email'],
			['key']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'expiresAt' :
				return new \Sql('NOW() + INTERVAL 3 DAY');

			case 'key' :
				return bin2hex(random_bytes(16));

			case 'status' :
				return Invite::PENDING;

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

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

	public function select(...$fields): InviteModel {
		return parent::select(...$fields);
	}

	public function where(...$data): InviteModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): InviteModel {
		return $this->where('id', ...$data);
	}

	public function whereCompany(...$data): InviteModel {
		return $this->where('company', ...$data);
	}

	public function whereEmail(...$data): InviteModel {
		return $this->where('email', ...$data);
	}

	public function whereEmployee(...$data): InviteModel {
		return $this->where('employee', ...$data);
	}

	public function whereExpiresAt(...$data): InviteModel {
		return $this->where('expiresAt', ...$data);
	}

	public function whereKey(...$data): InviteModel {
		return $this->where('key', ...$data);
	}

	public function whereStatus(...$data): InviteModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedBy(...$data): InviteModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class InviteCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Invite {

		$e = new Invite();

		if(empty($id)) {
			Invite::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Invite::getSelection();
		}

		if(Invite::model()
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
			$properties = Invite::getSelection();
		}

		if($sort !== NULL) {
			Invite::model()->sort($sort);
		}

		return Invite::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Invite {

		return new Invite(['id' => NULL]);

	}

	public static function create(Invite $e): void {

		Invite::model()->insert($e);

	}

	public static function update(Invite $e, array $properties): void {

		$e->expects(['id']);

		Invite::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Invite $e, array $properties): void {

		Invite::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Invite $e): void {

		$e->expects(['id']);

		Invite::model()->delete($e);

	}

}


class InvitePage extends \ModulePage {

	protected string $module = 'company\Invite';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? InviteLib::getPropertiesCreate(),
		   $propertiesUpdate ?? InviteLib::getPropertiesUpdate()
		);
	}

}
?>