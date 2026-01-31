<?php
namespace shop;

abstract class CatalogElement extends \Element {

	use \FilterElement;

	private static ?CatalogModel $model = NULL;

	const PRIVATE = 'private';
	const PRO = 'pro';

	const ACTIVE = 'active';
	const DELETED = 'deleted';

	public static function getSelection(): array {
		return Catalog::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): CatalogModel {
		if(self::$model === NULL) {
			self::$model = new CatalogModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Catalog::'.$failName, $arguments, $wrapper);
	}

}


class CatalogModel extends \ModuleModel {

	protected string $module = 'shop\Catalog';
	protected string $package = 'shop';
	protected string $table = 'shopCatalog';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'null' => TRUE, 'cast' => 'string'],
			'comment' => ['text24', 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'type' => ['enum', [\shop\Catalog::PRIVATE, \shop\Catalog::PRO], 'cast' => 'enum'],
			'products' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'status' => ['enum', [\shop\Catalog::ACTIVE, \shop\Catalog::DELETED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'comment', 'farm', 'type', 'products', 'status'
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

			case 'products' :
				return 0;

			case 'status' :
				return Catalog::ACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): CatalogModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CatalogModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CatalogModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): CatalogModel {
		return $this->where('name', ...$data);
	}

	public function whereComment(...$data): CatalogModel {
		return $this->where('comment', ...$data);
	}

	public function whereFarm(...$data): CatalogModel {
		return $this->where('farm', ...$data);
	}

	public function whereType(...$data): CatalogModel {
		return $this->where('type', ...$data);
	}

	public function whereProducts(...$data): CatalogModel {
		return $this->where('products', ...$data);
	}

	public function whereStatus(...$data): CatalogModel {
		return $this->where('status', ...$data);
	}


}


abstract class CatalogCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Catalog {

		$e = new Catalog();

		if(empty($id)) {
			Catalog::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Catalog::getSelection();
		}

		if(Catalog::model()
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
			$properties = Catalog::getSelection();
		}

		if($sort !== NULL) {
			Catalog::model()->sort($sort);
		}

		return Catalog::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Catalog {

		return new Catalog($properties);

	}

	public static function create(Catalog $e): void {

		Catalog::model()->insert($e);

	}

	public static function update(Catalog $e, array $properties): void {

		$e->expects(['id']);

		Catalog::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Catalog $e, array $properties): void {

		Catalog::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Catalog $e): void {

		$e->expects(['id']);

		Catalog::model()->delete($e);

	}

}


class CatalogPage extends \ModulePage {

	protected string $module = 'shop\Catalog';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CatalogLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CatalogLib::getPropertiesUpdate()
		);
	}

}
?>