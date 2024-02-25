<?php
namespace util;

abstract class CacheElement extends \Element {

	use \FilterElement;

	private static ?CacheModel $model = NULL;

	public static function getSelection(): array {
		return Cache::model()->getProperties();
	}

	public static function model(): CacheModel {
		if(self::$model === NULL) {
			self::$model = new CacheModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Cache::'.$failName, $arguments, $wrapper);
	}

}


class CacheModel extends \ModuleModel {

	protected string $module = 'util\Cache';
	protected string $package = 'util';
	protected string $table = 'utilCache';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'key' => ['text8', 'charset' => 'ascii', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'value' => ['binary32', 'cast' => 'binary'],
			'expireAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'key', 'value', 'expireAt'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['key']
		]);

	}

	public function select(...$fields): CacheModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CacheModel {
		return parent::where(...$data);
	}

	public function whereKey(...$data): CacheModel {
		return $this->where('key', ...$data);
	}

	public function whereValue(...$data): CacheModel {
		return $this->where('value', ...$data);
	}

	public function whereExpireAt(...$data): CacheModel {
		return $this->where('expireAt', ...$data);
	}


}


abstract class CacheCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Cache {

		$e = new Cache();

		if(empty($id)) {
			Cache::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Cache::getSelection();
		}

		if(Cache::model()
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
			$properties = Cache::getSelection();
		}

		if($sort !== NULL) {
			Cache::model()->sort($sort);
		}

		return Cache::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Cache {

		return new Cache(['id' => NULL]);

	}

	public static function create(Cache $e): void {

		Cache::model()->insert($e);

	}

	public static function update(Cache $e, array $properties): void {

		$e->expects(['id']);

		Cache::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Cache $e, array $properties): void {

		Cache::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Cache $e): void {

		$e->expects(['id']);

		Cache::model()->delete($e);

	}

}


class CachePage extends \ModulePage {

	protected string $module = 'util\Cache';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CacheLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CacheLib::getPropertiesUpdate()
		);
	}

}
?>