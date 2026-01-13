<?php
namespace game;

abstract class HistoryElement extends \Element {

	use \FilterElement;

	private static ?HistoryModel $model = NULL;

	public static function getSelection(): array {
		return History::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): HistoryModel {
		if(self::$model === NULL) {
			self::$model = new HistoryModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('History::'.$failName, $arguments, $wrapper);
	}

}


class HistoryModel extends \ModuleModel {

	protected string $module = 'game\History';
	protected string $package = 'game';
	protected string $table = 'gameHistory';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'time' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'message' => ['text16', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'time', 'message', 'createdAt'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): HistoryModel {
		return parent::select(...$fields);
	}

	public function where(...$data): HistoryModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): HistoryModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): HistoryModel {
		return $this->where('user', ...$data);
	}

	public function whereTime(...$data): HistoryModel {
		return $this->where('time', ...$data);
	}

	public function whereMessage(...$data): HistoryModel {
		return $this->where('message', ...$data);
	}

	public function whereCreatedAt(...$data): HistoryModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class HistoryCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): History {

		$e = new History();

		if(empty($id)) {
			History::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = History::getSelection();
		}

		if(History::model()
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
			$properties = History::getSelection();
		}

		if($sort !== NULL) {
			History::model()->sort($sort);
		}

		return History::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): History {

		return new History(['id' => NULL]);

	}

	public static function create(History $e): void {

		History::model()->insert($e);

	}

	public static function update(History $e, array $properties): void {

		$e->expects(['id']);

		History::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, History $e, array $properties): void {

		History::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(History $e): void {

		$e->expects(['id']);

		History::model()->delete($e);

	}

}


class HistoryPage extends \ModulePage {

	protected string $module = 'game\History';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? HistoryLib::getPropertiesCreate(),
		   $propertiesUpdate ?? HistoryLib::getPropertiesUpdate()
		);
	}

}
?>