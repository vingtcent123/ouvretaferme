<?php
namespace selling;

abstract class ArchiveElement extends \Element {

	use \FilterElement;

	private static ?ArchiveModel $model = NULL;

	public static function getSelection(): array {
		return Archive::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'selling\Archive';
	protected string $package = 'selling';
	protected string $table = 'sellingArchive';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'from' => ['date', 'cast' => 'string'],
			'to' => ['date', 'cast' => 'string'],
			'sha256' => ['textFixed', 'min' => 64, 'max' => 64, 'cast' => 'string'],
			'csv' => ['text24', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'from', 'to', 'sha256', 'csv', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'createdBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

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

	public function whereFrom(...$data): ArchiveModel {
		return $this->where('from', ...$data);
	}

	public function whereTo(...$data): ArchiveModel {
		return $this->where('to', ...$data);
	}

	public function whereSha256(...$data): ArchiveModel {
		return $this->where('sha256', ...$data);
	}

	public function whereCsv(...$data): ArchiveModel {
		return $this->where('csv', ...$data);
	}

	public function whereCreatedAt(...$data): ArchiveModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): ArchiveModel {
		return $this->where('createdBy', ...$data);
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

	public static function getNewElement(array $properties = []): Archive {

		return new Archive($properties);

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

	protected string $module = 'selling\Archive';

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