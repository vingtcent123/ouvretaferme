<?php
namespace shop;

abstract class CatalogShareElement extends \Element {

	use \FilterElement;

	private static ?CatalogShareModel $model = NULL;

	public static function getSelection(): array {
		return CatalogShare::model()->getProperties();
	}

	public static function model(): CatalogShareModel {
		if(self::$model === NULL) {
			self::$model = new CatalogShareModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('CatalogShare::'.$failName, $arguments, $wrapper);
	}

}


class CatalogShareModel extends \ModuleModel {

	protected string $module = 'shop\CatalogShare';
	protected string $package = 'shop';
	protected string $table = 'shopCatalogShare';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'catalog' => ['element32', 'shop\Catalog', 'cast' => 'element'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'catalog', 'shop'
		]);

		$this->propertiesToModule += [
			'catalog' => 'shop\Catalog',
			'shop' => 'shop\Shop',
		];

	}

	public function select(...$fields): CatalogShareModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CatalogShareModel {
		return parent::where(...$data);
	}

	public function whereCatalog(...$data): CatalogShareModel {
		return $this->where('catalog', ...$data);
	}

	public function whereShop(...$data): CatalogShareModel {
		return $this->where('shop', ...$data);
	}


}


abstract class CatalogShareCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): CatalogShare {

		$e = new CatalogShare();

		if(empty($id)) {
			CatalogShare::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = CatalogShare::getSelection();
		}

		if(CatalogShare::model()
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
			$properties = CatalogShare::getSelection();
		}

		if($sort !== NULL) {
			CatalogShare::model()->sort($sort);
		}

		return CatalogShare::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): CatalogShare {

		return new CatalogShare(['id' => NULL]);

	}

	public static function create(CatalogShare $e): void {

		CatalogShare::model()->insert($e);

	}

	public static function update(CatalogShare $e, array $properties): void {

		$e->expects(['id']);

		CatalogShare::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, CatalogShare $e, array $properties): void {

		CatalogShare::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(CatalogShare $e): void {

		$e->expects(['id']);

		CatalogShare::model()->delete($e);

	}

}


class CatalogSharePage extends \ModulePage {

	protected string $module = 'shop\CatalogShare';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CatalogShareLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CatalogShareLib::getPropertiesUpdate()
		);
	}

}
?>