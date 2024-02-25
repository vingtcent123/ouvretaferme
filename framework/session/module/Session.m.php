<?php
namespace session;

abstract class SessionElement extends \Element {

	use \FilterElement;

	private static ?SessionModel $model = NULL;

	public static function getSelection(): array {
		return Session::model()->getProperties();
	}

	public static function model(): SessionModel {
		if(self::$model === NULL) {
			self::$model = new SessionModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Session::'.$failName, $arguments, $wrapper);
	}

}


class SessionModel extends \ModuleModel {

	protected string $module = 'session\Session';
	protected string $package = 'session';
	protected string $table = 'session';

	protected ?int $split = NULL;
	protected ?string $splitOn = NULL;
	protected string $storage = 'memory';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'sid' => ['sid', 'unique' => TRUE, 'cast' => 'string'],
			'content' => ['binary8', 'min' => 0, 'max' => SessionLib::MAX_LENGTH, 'cast' => 'binary'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'sid', 'content', 'updatedAt', 'user'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['sid']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'updatedAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function split($sid): int {
		return crc32($sid) % SETTING(split);
	}

	public function select(...$fields): SessionModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SessionModel {
		return parent::where(...$data);
	}

	public function whereSid(...$data): SessionModel {
		return $this->where('sid', ...$data);
	}

	public function whereContent(...$data): SessionModel {
		return $this->where('content', ...$data);
	}

	public function whereUpdatedAt(...$data): SessionModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereUser(...$data): SessionModel {
		return $this->where('user', ...$data);
	}


}


abstract class SessionCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Session {

		$e = new Session();

		if(empty($id)) {
			Session::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Session::getSelection();
		}

		if(Session::model()
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
			$properties = Session::getSelection();
		}

		if($sort !== NULL) {
			Session::model()->sort($sort);
		}

		return Session::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Session {

		return new Session(['id' => NULL]);

	}

	public static function create(Session $e): void {

		Session::model()->insert($e);

	}

	public static function update(Session $e, array $properties): void {

		$e->expects(['id']);

		Session::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Session $e, array $properties): void {

		Session::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Session $e): void {

		$e->expects(['id']);

		Session::model()->delete($e);

	}

}


class SessionPage extends \ModulePage {

	protected string $module = 'session\Session';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SessionLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SessionLib::getPropertiesUpdate()
		);
	}

}
?>