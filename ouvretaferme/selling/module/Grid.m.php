<?php
namespace selling;

abstract class GridElement extends \Element {

	use \FilterElement;

	private static ?GridModel $model = NULL;

	public static function getSelection(): array {
		return Grid::model()->getProperties();
	}

	public static function model(): GridModel {
		if(self::$model === NULL) {
			self::$model = new GridModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Grid::'.$failName, $arguments, $wrapper);
	}

}


class GridModel extends \ModuleModel {

	protected string $module = 'selling\Grid';
	protected string $package = 'selling';
	protected string $table = 'sellingGrid';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'customer' => ['element32', 'selling\Customer', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'product' => ['element32', 'selling\Product', 'cast' => 'element'],
			'price' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'packaging' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'customer', 'farm', 'product', 'price', 'packaging', 'createdAt', 'updatedAt'
		]);

		$this->propertiesToModule += [
			'customer' => 'selling\Customer',
			'farm' => 'farm\Farm',
			'product' => 'selling\Product',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['product']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['customer', 'product']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): GridModel {
		return parent::select(...$fields);
	}

	public function where(...$data): GridModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): GridModel {
		return $this->where('id', ...$data);
	}

	public function whereCustomer(...$data): GridModel {
		return $this->where('customer', ...$data);
	}

	public function whereFarm(...$data): GridModel {
		return $this->where('farm', ...$data);
	}

	public function whereProduct(...$data): GridModel {
		return $this->where('product', ...$data);
	}

	public function wherePrice(...$data): GridModel {
		return $this->where('price', ...$data);
	}

	public function wherePackaging(...$data): GridModel {
		return $this->where('packaging', ...$data);
	}

	public function whereCreatedAt(...$data): GridModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): GridModel {
		return $this->where('updatedAt', ...$data);
	}


}


abstract class GridCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Grid {

		$e = new Grid();

		if(empty($id)) {
			Grid::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Grid::getSelection();
		}

		if(Grid::model()
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
			$properties = Grid::getSelection();
		}

		if($sort !== NULL) {
			Grid::model()->sort($sort);
		}

		return Grid::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Grid {

		return new Grid(['id' => NULL]);

	}

	public static function create(Grid $e): void {

		Grid::model()->insert($e);

	}

	public static function update(Grid $e, array $properties): void {

		$e->expects(['id']);

		Grid::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Grid $e, array $properties): void {

		Grid::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Grid $e): void {

		$e->expects(['id']);

		Grid::model()->delete($e);

	}

}


class GridPage extends \ModulePage {

	protected string $module = 'selling\Grid';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? GridLib::getPropertiesCreate(),
		   $propertiesUpdate ?? GridLib::getPropertiesUpdate()
		);
	}

}
?>