<?php
namespace shop;

abstract class SharedElement extends \Element {

	use \FilterElement;

	private static ?SharedModel $model = NULL;

	public static function getSelection(): array {
		return Shared::model()->getProperties();
	}

	public static function model(): SharedModel {
		if(self::$model === NULL) {
			self::$model = new SharedModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Shared::'.$failName, $arguments, $wrapper);
	}

}


class SharedModel extends \ModuleModel {

	protected string $module = 'shop\Shared';
	protected string $package = 'shop';
	protected string $table = 'shopShared';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'admin' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'shop', 'farm', 'admin', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
			'farm' => 'farm\Farm',
			'createdBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'admin' :
				return FALSE;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): SharedModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SharedModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SharedModel {
		return $this->where('id', ...$data);
	}

	public function whereShop(...$data): SharedModel {
		return $this->where('shop', ...$data);
	}

	public function whereFarm(...$data): SharedModel {
		return $this->where('farm', ...$data);
	}

	public function whereAdmin(...$data): SharedModel {
		return $this->where('admin', ...$data);
	}

	public function whereCreatedAt(...$data): SharedModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): SharedModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class SharedCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Shared {

		$e = new Shared();

		if(empty($id)) {
			Shared::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Shared::getSelection();
		}

		if(Shared::model()
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
			$properties = Shared::getSelection();
		}

		if($sort !== NULL) {
			Shared::model()->sort($sort);
		}

		return Shared::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Shared {

		return new Shared(['id' => NULL]);

	}

	public static function create(Shared $e): void {

		Shared::model()->insert($e);

	}

	public static function update(Shared $e, array $properties): void {

		$e->expects(['id']);

		Shared::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Shared $e, array $properties): void {

		Shared::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Shared $e): void {

		$e->expects(['id']);

		Shared::model()->delete($e);

	}

}


class SharedPage extends \ModulePage {

	protected string $module = 'shop\Shared';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SharedLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SharedLib::getPropertiesUpdate()
		);
	}

}
?>