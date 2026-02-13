<?php
namespace data;

abstract class DataElement extends \Element {

	use \FilterElement;

	private static ?DataModel $model = NULL;

	const HOURLY = 'hourly';
	const DAILY = 'daily';

	public static function getSelection(): array {
		return Data::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): DataModel {
		if(self::$model === NULL) {
			self::$model = new DataModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Data::'.$failName, $arguments, $wrapper);
	}

}


class DataModel extends \ModuleModel {

	protected string $module = 'data\Data';
	protected string $package = 'data';
	protected string $table = 'data';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'fqn' => ['text8', 'charset' => 'ascii', 'cast' => 'string'],
			'frequency' => ['enum', [\data\Data::HOURLY, \data\Data::DAILY], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'fqn', 'frequency'
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'frequency' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): DataModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DataModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DataModel {
		return $this->where('id', ...$data);
	}

	public function whereFqn(...$data): DataModel {
		return $this->where('fqn', ...$data);
	}

	public function whereFrequency(...$data): DataModel {
		return $this->where('frequency', ...$data);
	}


}


abstract class DataCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Data {

		$e = new Data();

		if(empty($id)) {
			Data::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Data::getSelection();
		}

		if(Data::model()
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
			$properties = Data::getSelection();
		}

		if($sort !== NULL) {
			Data::model()->sort($sort);
		}

		return Data::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Data {

		return new Data($properties);

	}

	public static function create(Data $e): void {

		Data::model()->insert($e);

	}

	public static function update(Data $e, array $properties): void {

		$e->expects(['id']);

		Data::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Data $e, array $properties): void {

		Data::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Data $e): void {

		$e->expects(['id']);

		Data::model()->delete($e);

	}

}


class DataPage extends \ModulePage {

	protected string $module = 'data\Data';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DataLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DataLib::getPropertiesUpdate()
		);
	}

}
?>