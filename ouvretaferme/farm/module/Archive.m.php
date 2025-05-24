<?php
namespace farm;

abstract class ArchiveElement extends \Element {

	use \FilterElement;

	private static ?ArchiveModel $model = NULL;

	public static function getSelection(): array {
		return Archive::model()->getProperties();
	}

	public static function model(): ArchiveModel {
		if(self::$model === NULL) {
			self::$model = new ArchiveModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Archive::'.$failName, $arguments, $wrapper);
	}

}


class ArchiveModel extends \ModuleModel {

	protected string $module = 'farm\Archive';
	protected string $package = 'farm';
	protected string $table = 'farmArchive';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'hash' => ['textFixed', 'min' => 66, 'max' => 66, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'requestedBy' => ['element32', 'user\User', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'season', 'hash', 'requestedBy', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'requestedBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'season']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'requestedBy' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): ArchiveModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ArchiveModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ArchiveModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): ArchiveModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeason(...$data): ArchiveModel {
		return $this->where('season', ...$data);
	}

	public function whereHash(...$data): ArchiveModel {
		return $this->where('hash', ...$data);
	}

	public function whereRequestedBy(...$data): ArchiveModel {
		return $this->where('requestedBy', ...$data);
	}

	public function whereCreatedAt(...$data): ArchiveModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class ArchiveCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Archive {

		$e = new Archive();

		if(empty($id)) {
			Archive::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Archive::getSelection();
		}

		if(Archive::model()
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
			$properties = Archive::getSelection();
		}

		if($sort !== NULL) {
			Archive::model()->sort($sort);
		}

		return Archive::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Archive {

		return new Archive(['id' => NULL]);

	}

	public static function create(Archive $e): void {

		Archive::model()->insert($e);

	}

	public static function update(Archive $e, array $properties): void {

		$e->expects(['id']);

		Archive::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Archive $e, array $properties): void {

		Archive::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Archive $e): void {

		$e->expects(['id']);

		Archive::model()->delete($e);

	}

}


class ArchivePage extends \ModulePage {

	protected string $module = 'farm\Archive';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ArchiveLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ArchiveLib::getPropertiesUpdate()
		);
	}

}
?>