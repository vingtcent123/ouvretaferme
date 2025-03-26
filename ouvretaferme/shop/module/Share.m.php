<?php
namespace shop;

abstract class ShareElement extends \Element {

	use \FilterElement;

	private static ?ShareModel $model = NULL;

	public static function getSelection(): array {
		return Share::model()->getProperties();
	}

	public static function model(): ShareModel {
		if(self::$model === NULL) {
			self::$model = new ShareModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Share::'.$failName, $arguments, $wrapper);
	}

}


class ShareModel extends \ModuleModel {

	protected string $module = 'shop\Share';
	protected string $package = 'shop';
	protected string $table = 'shopShare';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'shop', 'farm', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
			'farm' => 'farm\Farm',
			'createdBy' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'shop']
		]);

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

	public function select(...$fields): ShareModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ShareModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ShareModel {
		return $this->where('id', ...$data);
	}

	public function whereShop(...$data): ShareModel {
		return $this->where('shop', ...$data);
	}

	public function whereFarm(...$data): ShareModel {
		return $this->where('farm', ...$data);
	}

	public function whereCreatedAt(...$data): ShareModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): ShareModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class ShareCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Share {

		$e = new Share();

		if(empty($id)) {
			Share::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Share::getSelection();
		}

		if(Share::model()
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
			$properties = Share::getSelection();
		}

		if($sort !== NULL) {
			Share::model()->sort($sort);
		}

		return Share::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Share {

		return new Share(['id' => NULL]);

	}

	public static function create(Share $e): void {

		Share::model()->insert($e);

	}

	public static function update(Share $e, array $properties): void {

		$e->expects(['id']);

		Share::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Share $e, array $properties): void {

		Share::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Share $e): void {

		$e->expects(['id']);

		Share::model()->delete($e);

	}

}


class SharePage extends \ModulePage {

	protected string $module = 'shop\Share';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ShareLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ShareLib::getPropertiesUpdate()
		);
	}

}
?>