<?php
namespace selling;

abstract class GroupElement extends \Element {

	use \FilterElement;

	private static ?GroupModel $model = NULL;

	public static function getSelection(): array {
		return Group::model()->getProperties();
	}

	public static function model(): GroupModel {
		if(self::$model === NULL) {
			self::$model = new GroupModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Group::'.$failName, $arguments, $wrapper);
	}

}


class GroupModel extends \ModuleModel {

	protected string $module = 'selling\Group';
	protected string $package = 'selling';
	protected string $table = 'sellingGroup';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'color' => ['color', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'color', 'farm', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
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

	public function select(...$fields): GroupModel {
		return parent::select(...$fields);
	}

	public function where(...$data): GroupModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): GroupModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): GroupModel {
		return $this->where('name', ...$data);
	}

	public function whereColor(...$data): GroupModel {
		return $this->where('color', ...$data);
	}

	public function whereFarm(...$data): GroupModel {
		return $this->where('farm', ...$data);
	}

	public function whereCreatedAt(...$data): GroupModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class GroupCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Group {

		$e = new Group();

		if(empty($id)) {
			Group::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Group::getSelection();
		}

		if(Group::model()
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
			$properties = Group::getSelection();
		}

		if($sort !== NULL) {
			Group::model()->sort($sort);
		}

		return Group::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Group {

		return new Group(['id' => NULL]);

	}

	public static function create(Group $e): void {

		Group::model()->insert($e);

	}

	public static function update(Group $e, array $properties): void {

		$e->expects(['id']);

		Group::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Group $e, array $properties): void {

		Group::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Group $e): void {

		$e->expects(['id']);

		Group::model()->delete($e);

	}

}


class GroupPage extends \ModulePage {

	protected string $module = 'selling\Group';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? GroupLib::getPropertiesCreate(),
		   $propertiesUpdate ?? GroupLib::getPropertiesUpdate()
		);
	}

}
?>