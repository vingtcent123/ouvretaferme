<?php
namespace account;

abstract class LogElement extends \Element {

	use \FilterElement;

	private static ?LogModel $model = NULL;

	public static function getSelection(): array {
		return Log::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'account\Log';
	protected string $package = 'account';
	protected string $table = 'accountLog';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'action' => ['text8', 'cast' => 'string'],
			'element' => ['text8', 'cast' => 'string'],
			'params' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'doneBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'action', 'element', 'params', 'doneBy', 'createdAt'
		]);

		$this->propertiesToModule += [
			'doneBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'params' :
				return [];

			case 'doneBy' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'params' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'params' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

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

	public function whereAction(...$data): LogModel {
		return $this->where('action', ...$data);
	}

	public function whereElement(...$data): LogModel {
		return $this->where('element', ...$data);
	}

	public function whereParams(...$data): LogModel {
		return $this->where('params', ...$data);
	}

	public function whereDoneBy(...$data): LogModel {
		return $this->where('doneBy', ...$data);
	}

	public function whereCreatedAt(...$data): LogModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class LogCrud extends \ModuleCrud {

 private static array $cache = [];

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

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

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

	protected string $module = 'account\Log';

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