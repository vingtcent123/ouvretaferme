<?php
namespace shop;

abstract class RelationElement extends \Element {

	use \FilterElement;

	private static ?RelationModel $model = NULL;

	public static function getSelection(): array {
		return Relation::model()->getProperties();
	}

	public static function model(): RelationModel {
		if(self::$model === NULL) {
			self::$model = new RelationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Relation::'.$failName, $arguments, $wrapper);
	}

}


class RelationModel extends \ModuleModel {

	protected string $module = 'shop\Relation';
	protected string $package = 'shop';
	protected string $table = 'shopRelation';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'parent' => ['element32', 'shop\Product', 'cast' => 'element'],
			'child' => ['element32', 'shop\Product', 'unique' => TRUE, 'cast' => 'element'],
			'position' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'parent', 'child', 'position', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'parent' => 'shop\Product',
			'child' => 'shop\Product',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['parent']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['child']
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

	public function select(...$fields): RelationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): RelationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): RelationModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): RelationModel {
		return $this->where('farm', ...$data);
	}

	public function whereParent(...$data): RelationModel {
		return $this->where('parent', ...$data);
	}

	public function whereChild(...$data): RelationModel {
		return $this->where('child', ...$data);
	}

	public function wherePosition(...$data): RelationModel {
		return $this->where('position', ...$data);
	}

	public function whereCreatedAt(...$data): RelationModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class RelationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Relation {

		$e = new Relation();

		if(empty($id)) {
			Relation::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Relation::getSelection();
		}

		if(Relation::model()
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
			$properties = Relation::getSelection();
		}

		if($sort !== NULL) {
			Relation::model()->sort($sort);
		}

		return Relation::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Relation {

		return new Relation(['id' => NULL]);

	}

	public static function create(Relation $e): void {

		Relation::model()->insert($e);

	}

	public static function update(Relation $e, array $properties): void {

		$e->expects(['id']);

		Relation::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Relation $e, array $properties): void {

		Relation::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Relation $e): void {

		$e->expects(['id']);

		Relation::model()->delete($e);

	}

}


class RelationPage extends \ModulePage {

	protected string $module = 'shop\Relation';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? RelationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? RelationLib::getPropertiesUpdate()
		);
	}

}
?>