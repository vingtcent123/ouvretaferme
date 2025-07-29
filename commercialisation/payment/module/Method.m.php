<?php
namespace payment;

abstract class MethodElement extends \Element {

	use \FilterElement;

	private static ?MethodModel $model = NULL;

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';
	const DELETED = 'deleted';

	const SELLING = 1;
	const ACCOUNTING = 2;

	public static function getSelection(): array {
		return Method::model()->getProperties();
	}

	public static function model(): MethodModel {
		if(self::$model === NULL) {
			self::$model = new MethodModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Method::'.$failName, $arguments, $wrapper);
	}

}


class MethodModel extends \ModuleModel {

	protected string $module = 'payment\Method';
	protected string $package = 'payment';
	protected string $table = 'paymentMethod';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'fqn' => ['fqn', 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'online' => ['bool', 'cast' => 'bool'],
			'status' => ['enum', [\payment\Method::ACTIVE, \payment\Method::INACTIVE, \payment\Method::DELETED], 'cast' => 'enum'],
			'use' => ['set', [\payment\Method::SELLING, \payment\Method::ACCOUNTING], 'cast' => 'set'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'fqn', 'farm', 'online', 'status', 'use'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'fqn']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'online' :
				return FALSE;

			case 'status' :
				return Method::ACTIVE;

			case 'use' :
				return new \Set(Method::SELLING);

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

	public function select(...$fields): MethodModel {
		return parent::select(...$fields);
	}

	public function where(...$data): MethodModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): MethodModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): MethodModel {
		return $this->where('name', ...$data);
	}

	public function whereFqn(...$data): MethodModel {
		return $this->where('fqn', ...$data);
	}

	public function whereFarm(...$data): MethodModel {
		return $this->where('farm', ...$data);
	}

	public function whereOnline(...$data): MethodModel {
		return $this->where('online', ...$data);
	}

	public function whereStatus(...$data): MethodModel {
		return $this->where('status', ...$data);
	}

	public function whereUse(...$data): MethodModel {
		return $this->where('use', ...$data);
	}


}


abstract class MethodCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Method {

		$e = new Method();

		if(empty($id)) {
			Method::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Method::getSelection();
		}

		if(Method::model()
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
			$properties = Method::getSelection();
		}

		if($sort !== NULL) {
			Method::model()->sort($sort);
		}

		return Method::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Method {

		$e = new Method();

		if(empty($fqn)) {
			Method::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Method::getSelection();
		}

		if(Method::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Method::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Method::getSelection();
		}

		return Method::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Method {

		return new Method(['id' => NULL]);

	}

	public static function create(Method $e): void {

		Method::model()->insert($e);

	}

	public static function update(Method $e, array $properties): void {

		$e->expects(['id']);

		Method::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Method $e, array $properties): void {

		Method::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Method $e): void {

		$e->expects(['id']);

		Method::model()->delete($e);

	}

}


class MethodPage extends \ModulePage {

	protected string $module = 'payment\Method';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? MethodLib::getPropertiesCreate(),
		   $propertiesUpdate ?? MethodLib::getPropertiesUpdate()
		);
	}

}
?>