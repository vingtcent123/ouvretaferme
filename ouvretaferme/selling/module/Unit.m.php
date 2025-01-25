<?php
namespace selling;

abstract class UnitElement extends \Element {

	use \FilterElement;

	private static ?UnitModel $model = NULL;

	const INTEGER = 'integer';
	const DECIMAL = 'decimal';

	public static function getSelection(): array {
		return Unit::model()->getProperties();
	}

	public static function model(): UnitModel {
		if(self::$model === NULL) {
			self::$model = new UnitModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Unit::'.$failName, $arguments, $wrapper);
	}

}


class UnitModel extends \ModuleModel {

	protected string $module = 'selling\Unit';
	protected string $package = 'selling';
	protected string $table = 'sellingUnit';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'singular' => ['text8', 'min' => 1, 'max' => 15, 'collate' => 'general', 'cast' => 'string'],
			'plural' => ['text8', 'min' => 1, 'max' => 15, 'collate' => 'general', 'cast' => 'string'],
			'short' => ['text8', 'min' => 1, 'max' => 3, 'collate' => 'general', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'fqn' => ['fqn', 'null' => TRUE, 'cast' => 'string'],
			'by' => ['bool', 'cast' => 'bool'],
			'type' => ['enum', [\selling\Unit::INTEGER, \selling\Unit::DECIMAL], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'singular', 'plural', 'short', 'farm', 'fqn', 'by', 'type'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'singular']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'by' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): UnitModel {
		return parent::select(...$fields);
	}

	public function where(...$data): UnitModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): UnitModel {
		return $this->where('id', ...$data);
	}

	public function whereSingular(...$data): UnitModel {
		return $this->where('singular', ...$data);
	}

	public function wherePlural(...$data): UnitModel {
		return $this->where('plural', ...$data);
	}

	public function whereShort(...$data): UnitModel {
		return $this->where('short', ...$data);
	}

	public function whereFarm(...$data): UnitModel {
		return $this->where('farm', ...$data);
	}

	public function whereFqn(...$data): UnitModel {
		return $this->where('fqn', ...$data);
	}

	public function whereBy(...$data): UnitModel {
		return $this->where('by', ...$data);
	}

	public function whereType(...$data): UnitModel {
		return $this->where('type', ...$data);
	}


}


abstract class UnitCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Unit {

		$e = new Unit();

		if(empty($id)) {
			Unit::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Unit::getSelection();
		}

		if(Unit::model()
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
			$properties = Unit::getSelection();
		}

		if($sort !== NULL) {
			Unit::model()->sort($sort);
		}

		return Unit::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Unit {

		$e = new Unit();

		if(empty($fqn)) {
			Unit::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Unit::getSelection();
		}

		if(Unit::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Unit::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Unit::getSelection();
		}

		return Unit::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Unit {

		return new Unit(['id' => NULL]);

	}

	public static function create(Unit $e): void {

		Unit::model()->insert($e);

	}

	public static function update(Unit $e, array $properties): void {

		$e->expects(['id']);

		Unit::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Unit $e, array $properties): void {

		Unit::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Unit $e): void {

		$e->expects(['id']);

		Unit::model()->delete($e);

	}

}


class UnitPage extends \ModulePage {

	protected string $module = 'selling\Unit';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? UnitLib::getPropertiesCreate(),
		   $propertiesUpdate ?? UnitLib::getPropertiesUpdate()
		);
	}

}
?>