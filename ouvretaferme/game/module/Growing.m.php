<?php
namespace game;

abstract class GrowingElement extends \Element {

	use \FilterElement;

	private static ?GrowingModel $model = NULL;

	public static function getSelection(): array {
		return Growing::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): GrowingModel {
		if(self::$model === NULL) {
			self::$model = new GrowingModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Growing::'.$failName, $arguments, $wrapper);
	}

}


class GrowingModel extends \ModuleModel {

	protected string $module = 'game\Growing';
	protected string $package = 'game';
	protected string $table = 'gameGrowing';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'fqn' => ['fqn', 'unique' => TRUE, 'cast' => 'string'],
			'harvest' => ['int8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'days' => ['int8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'bonusWatering' => ['int8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'fqn', 'harvest', 'days', 'bonusWatering'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fqn']
		]);

	}

	public function select(...$fields): GrowingModel {
		return parent::select(...$fields);
	}

	public function where(...$data): GrowingModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): GrowingModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): GrowingModel {
		return $this->where('name', ...$data);
	}

	public function whereFqn(...$data): GrowingModel {
		return $this->where('fqn', ...$data);
	}

	public function whereHarvest(...$data): GrowingModel {
		return $this->where('harvest', ...$data);
	}

	public function whereDays(...$data): GrowingModel {
		return $this->where('days', ...$data);
	}

	public function whereBonusWatering(...$data): GrowingModel {
		return $this->where('bonusWatering', ...$data);
	}


}


abstract class GrowingCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Growing {

		$e = new Growing();

		if(empty($id)) {
			Growing::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Growing::getSelection();
		}

		if(Growing::model()
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
			$properties = Growing::getSelection();
		}

		if($sort !== NULL) {
			Growing::model()->sort($sort);
		}

		return Growing::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Growing {

		$e = new Growing();

		if(empty($fqn)) {
			Growing::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Growing::getSelection();
		}

		if(Growing::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Growing::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Growing::getSelection();
		}

		return Growing::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Growing {

		return new Growing($properties);

	}

	public static function create(Growing $e): void {

		Growing::model()->insert($e);

	}

	public static function update(Growing $e, array $properties): void {

		$e->expects(['id']);

		Growing::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Growing $e, array $properties): void {

		Growing::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Growing $e): void {

		$e->expects(['id']);

		Growing::model()->delete($e);

	}

}


class GrowingPage extends \ModulePage {

	protected string $module = 'game\Growing';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? GrowingLib::getPropertiesCreate(),
		   $propertiesUpdate ?? GrowingLib::getPropertiesUpdate()
		);
	}

}
?>