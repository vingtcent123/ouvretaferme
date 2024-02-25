<?php
namespace dev;

abstract class ErrorElement extends \Element {

	use \FilterElement;

	private static ?ErrorModel $model = NULL;

	const EXCEPTION = 'exception';
	const PHP = 'php';
	const UNEXPECTED = 'unexpected';
	const NGINX = 'nginx';
	const IOS = 'ios';
	const ANDROID = 'android';

	const OPEN = 'open';
	const CLOSE = 'close';

	public static function getSelection(): array {
		return Error::model()->getProperties();
	}

	public static function model(): ErrorModel {
		if(self::$model === NULL) {
			self::$model = new ErrorModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Error::'.$failName, $arguments, $wrapper);
	}

}


class ErrorModel extends \ModuleModel {

	protected string $module = 'dev\Error';
	protected string $package = 'dev';
	protected string $table = 'devError';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'message' => ['text24', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'code' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'app' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'file' => ['text8', 'min' => 1, 'max' => NULL, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'line' => ['int32', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'mode' => ['text8', 'min' => 1, 'max' => NULL, 'charset' => 'ascii', 'cast' => 'string'],
			'modeVersion' => ['text8', 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'tag' => ['text8', 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'method' => ['text8', 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'type' => ['enum', [\dev\Error::EXCEPTION, \dev\Error::PHP, \dev\Error::UNEXPECTED, \dev\Error::NGINX, \dev\Error::IOS, \dev\Error::ANDROID], 'cast' => 'enum'],
			'request' => ['text16', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\dev\Error::OPEN, \dev\Error::CLOSE], 'cast' => 'enum'],
			'statusUpdatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'table' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'server' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'browser' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'device' => ['text8', 'min' => 1, 'max' => NULL, 'charset' => 'ascii', 'cast' => 'string'],
			'referer' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deprecated' => ['bool', 'cast' => 'bool'],
			'exported' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'message', 'code', 'app', 'file', 'line', 'user', 'mode', 'modeVersion', 'tag', 'method', 'type', 'request', 'createdAt', 'status', 'statusUpdatedAt', 'table', 'server', 'browser', 'device', 'referer', 'deprecated', 'exported'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['file'],
			['createdAt']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'mode' :
				return \Route::getRequestedWith();

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Error::OPEN;

			case 'deprecated' :
				return FALSE;

			case 'exported' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): ErrorModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ErrorModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ErrorModel {
		return $this->where('id', ...$data);
	}

	public function whereMessage(...$data): ErrorModel {
		return $this->where('message', ...$data);
	}

	public function whereCode(...$data): ErrorModel {
		return $this->where('code', ...$data);
	}

	public function whereApp(...$data): ErrorModel {
		return $this->where('app', ...$data);
	}

	public function whereFile(...$data): ErrorModel {
		return $this->where('file', ...$data);
	}

	public function whereLine(...$data): ErrorModel {
		return $this->where('line', ...$data);
	}

	public function whereUser(...$data): ErrorModel {
		return $this->where('user', ...$data);
	}

	public function whereMode(...$data): ErrorModel {
		return $this->where('mode', ...$data);
	}

	public function whereModeVersion(...$data): ErrorModel {
		return $this->where('modeVersion', ...$data);
	}

	public function whereTag(...$data): ErrorModel {
		return $this->where('tag', ...$data);
	}

	public function whereMethod(...$data): ErrorModel {
		return $this->where('method', ...$data);
	}

	public function whereType(...$data): ErrorModel {
		return $this->where('type', ...$data);
	}

	public function whereRequest(...$data): ErrorModel {
		return $this->where('request', ...$data);
	}

	public function whereCreatedAt(...$data): ErrorModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): ErrorModel {
		return $this->where('status', ...$data);
	}

	public function whereStatusUpdatedAt(...$data): ErrorModel {
		return $this->where('statusUpdatedAt', ...$data);
	}

	public function whereTable(...$data): ErrorModel {
		return $this->where('table', ...$data);
	}

	public function whereServer(...$data): ErrorModel {
		return $this->where('server', ...$data);
	}

	public function whereBrowser(...$data): ErrorModel {
		return $this->where('browser', ...$data);
	}

	public function whereDevice(...$data): ErrorModel {
		return $this->where('device', ...$data);
	}

	public function whereReferer(...$data): ErrorModel {
		return $this->where('referer', ...$data);
	}

	public function whereDeprecated(...$data): ErrorModel {
		return $this->where('deprecated', ...$data);
	}

	public function whereExported(...$data): ErrorModel {
		return $this->where('exported', ...$data);
	}


}


abstract class ErrorCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Error {

		$e = new Error();

		if(empty($id)) {
			Error::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Error::getSelection();
		}

		if(Error::model()
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
			$properties = Error::getSelection();
		}

		if($sort !== NULL) {
			Error::model()->sort($sort);
		}

		return Error::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Error {

		return new Error(['id' => NULL]);

	}

	public static function create(Error $e): void {

		Error::model()->insert($e);

	}

	public static function update(Error $e, array $properties): void {

		$e->expects(['id']);

		Error::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Error $e, array $properties): void {

		Error::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Error $e): void {

		$e->expects(['id']);

		Error::model()->delete($e);

	}

}


class ErrorPage extends \ModulePage {

	protected string $module = 'dev\Error';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ErrorLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ErrorLib::getPropertiesUpdate()
		);
	}

}
?>