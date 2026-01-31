<?php
namespace selling;

abstract class CustomerGroupElement extends \Element {

	use \FilterElement;

	private static ?CustomerGroupModel $model = NULL;

	const PRIVATE = 'private';
	const PRO = 'pro';

	public static function getSelection(): array {
		return CustomerGroup::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): CustomerGroupModel {
		if(self::$model === NULL) {
			self::$model = new CustomerGroupModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('CustomerGroup::'.$failName, $arguments, $wrapper);
	}

}


class CustomerGroupModel extends \ModuleModel {

	protected string $module = 'selling\CustomerGroup';
	protected string $package = 'selling';
	protected string $table = 'sellingCustomerGroup';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'type' => ['enum', [\selling\CustomerGroup::PRIVATE, \selling\CustomerGroup::PRO], 'cast' => 'enum'],
			'color' => ['color', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'type', 'color', 'farm', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'color' :
				return '#373737';

			case 'createdAt' :
				return new \Sql('NOW()');

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

	public function select(...$fields): CustomerGroupModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CustomerGroupModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CustomerGroupModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): CustomerGroupModel {
		return $this->where('name', ...$data);
	}

	public function whereType(...$data): CustomerGroupModel {
		return $this->where('type', ...$data);
	}

	public function whereColor(...$data): CustomerGroupModel {
		return $this->where('color', ...$data);
	}

	public function whereFarm(...$data): CustomerGroupModel {
		return $this->where('farm', ...$data);
	}

	public function whereCreatedAt(...$data): CustomerGroupModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class CustomerGroupCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): CustomerGroup {

		$e = new CustomerGroup();

		if(empty($id)) {
			CustomerGroup::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = CustomerGroup::getSelection();
		}

		if(CustomerGroup::model()
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
			$properties = CustomerGroup::getSelection();
		}

		if($sort !== NULL) {
			CustomerGroup::model()->sort($sort);
		}

		return CustomerGroup::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): CustomerGroup {

		return new CustomerGroup($properties);

	}

	public static function create(CustomerGroup $e): void {

		CustomerGroup::model()->insert($e);

	}

	public static function update(CustomerGroup $e, array $properties): void {

		$e->expects(['id']);

		CustomerGroup::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, CustomerGroup $e, array $properties): void {

		CustomerGroup::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(CustomerGroup $e): void {

		$e->expects(['id']);

		CustomerGroup::model()->delete($e);

	}

}


class CustomerGroupPage extends \ModulePage {

	protected string $module = 'selling\CustomerGroup';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CustomerGroupLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CustomerGroupLib::getPropertiesUpdate()
		);
	}

}
?>