<?php
namespace user;

abstract class LogElement extends \Element {

	use \FilterElement;

	private static ?LogModel $model = NULL;

	const LOGIN = 'login';
	const LOGOUT = 'logout';
	const LOGIN_EXTERNAL = 'login-external';
	const LOGIN_AUTO = 'login-auto';

	const WEB = 'web';
	const APP = 'app';
	const MOBILE_WEB = 'mobile-web';
	const TABLET_WEB = 'tablet-web';
	const CRAWLER = 'crawler';

	public static function getSelection(): array {
		return Log::model()->getProperties();
	}

	public static function model(): LogModel {
		if(self::$model === NULL) {
			self::$model = new LogModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Log::'.$failName, $arguments, $wrapper);
	}

}


class LogModel extends \ModuleModel {

	protected string $module = 'user\Log';
	protected string $package = 'user';
	protected string $table = 'userLog';

	protected ?int $split = NULL;
	protected ?string $splitOn = NULL;

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'action' => ['enum', [\user\Log::LOGIN, \user\Log::LOGOUT, \user\Log::LOGIN_EXTERNAL, \user\Log::LOGIN_AUTO], 'cast' => 'enum'],
			'ip' => ['ipv4', 'cast' => 'string'],
			'sid' => ['sid', 'cast' => 'string'],
			'device' => ['enum', [\user\Log::WEB, \user\Log::APP, \user\Log::MOBILE_WEB, \user\Log::TABLET_WEB, \user\Log::CRAWLER], 'cast' => 'enum'],
			'deviceVersion' => ['int16', 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'userAction' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'user', 'action', 'ip', 'sid', 'device', 'deviceVersion', 'createdAt', 'userAction'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'userAction' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['user', 'createdAt']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'ip' :
				return getIp();

			case 'sid' :
				return session_id();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'action' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'ip' :
				return $value === NULL ? NULL : (int)first(unpack('l', pack('l', ip2long($value))));

			case 'device' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'ip' :
				return $value === NULL ? NULL : long2ip($value);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): LogModel {
		return parent::select(...$fields);
	}

	public function where(...$data): LogModel {
		return parent::where(...$data);
	}

	public function whereUser(...$data): LogModel {
		return $this->where('user', ...$data);
	}

	public function whereAction(...$data): LogModel {
		return $this->where('action', ...$data);
	}

	public function whereIp(...$data): LogModel {
		return $this->where('ip', ...$data);
	}

	public function whereSid(...$data): LogModel {
		return $this->where('sid', ...$data);
	}

	public function whereDevice(...$data): LogModel {
		return $this->where('device', ...$data);
	}

	public function whereDeviceVersion(...$data): LogModel {
		return $this->where('deviceVersion', ...$data);
	}

	public function whereCreatedAt(...$data): LogModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUserAction(...$data): LogModel {
		return $this->where('userAction', ...$data);
	}


}


abstract class LogCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Log {

		$e = new Log();

		if(empty($id)) {
			Log::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Log::getSelection();
		}

		if(Log::model()
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
			$properties = Log::getSelection();
		}

		if($sort !== NULL) {
			Log::model()->sort($sort);
		}

		return Log::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Log {

		return new Log(['id' => NULL]);

	}

	public static function create(Log $e): void {

		Log::model()->insert($e);

	}

	public static function update(Log $e, array $properties): void {

		$e->expects(['id']);

		Log::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Log $e, array $properties): void {

		Log::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Log $e): void {

		$e->expects(['id']);

		Log::model()->delete($e);

	}

}


class LogPage extends \ModulePage {

	protected string $module = 'user\Log';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? LogLib::getPropertiesCreate(),
		   $propertiesUpdate ?? LogLib::getPropertiesUpdate()
		);
	}

}
?>