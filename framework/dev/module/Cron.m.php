<?php
namespace dev;

abstract class CronElement extends \Element {

	use \FilterElement;

	private static ?CronModel $model = NULL;

	const RUNNING = 'running';
	const ERROR = 'error';
	const SUCCESS = 'success';

	public static function getSelection(): array {
		return Cron::model()->getProperties();
	}

	public static function model(): CronModel {
		if(self::$model === NULL) {
			self::$model = new CronModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Cron::'.$failName, $arguments, $wrapper);
	}

}


class CronModel extends \ModuleModel {

	protected string $module = 'dev\Cron';
	protected string $package = 'dev';
	protected string $table = 'devCron';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'request' => ['text8', 'charset' => 'ascii', 'cast' => 'string'],
			'time' => ['float32', 'null' => TRUE, 'cast' => 'float'],
			'status' => ['enum', [\dev\Cron::RUNNING, \dev\Cron::ERROR, \dev\Cron::SUCCESS], 'cast' => 'enum'],
			'beginAt' => ['datetime', 'cast' => 'string'],
			'endAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'output' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'lastError' => ['text16', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'request', 'time', 'status', 'beginAt', 'endAt', 'output', 'lastError'
		]);

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['request'],
			['beginAt']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Cron::RUNNING;

			case 'beginAt' :
				return new \Sql('NOW()');

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

	public function select(...$fields): CronModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CronModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CronModel {
		return $this->where('id', ...$data);
	}

	public function whereRequest(...$data): CronModel {
		return $this->where('request', ...$data);
	}

	public function whereTime(...$data): CronModel {
		return $this->where('time', ...$data);
	}

	public function whereStatus(...$data): CronModel {
		return $this->where('status', ...$data);
	}

	public function whereBeginAt(...$data): CronModel {
		return $this->where('beginAt', ...$data);
	}

	public function whereEndAt(...$data): CronModel {
		return $this->where('endAt', ...$data);
	}

	public function whereOutput(...$data): CronModel {
		return $this->where('output', ...$data);
	}

	public function whereLastError(...$data): CronModel {
		return $this->where('lastError', ...$data);
	}


}


abstract class CronCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Cron {

		$e = new Cron();

		if(empty($id)) {
			Cron::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Cron::getSelection();
		}

		if(Cron::model()
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
			$properties = Cron::getSelection();
		}

		if($sort !== NULL) {
			Cron::model()->sort($sort);
		}

		return Cron::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Cron {

		return new Cron(['id' => NULL]);

	}

	public static function create(Cron $e): void {

		Cron::model()->insert($e);

	}

	public static function update(Cron $e, array $properties): void {

		$e->expects(['id']);

		Cron::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Cron $e, array $properties): void {

		Cron::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Cron $e): void {

		$e->expects(['id']);

		Cron::model()->delete($e);

	}

}


class CronPage extends \ModulePage {

	protected string $module = 'dev\Cron';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CronLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CronLib::getPropertiesUpdate()
		);
	}

}
?>