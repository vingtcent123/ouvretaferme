<?php
namespace farm;

abstract class SupplierElement extends \Element {

	use \FilterElement;

	private static ?SupplierModel $model = NULL;

	public static function getSelection(): array {
		return Supplier::model()->getProperties();
	}

	public static function model(): SupplierModel {
		if(self::$model === NULL) {
			self::$model = new SupplierModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Supplier::'.$failName, $arguments, $wrapper);
	}

}


class SupplierModel extends \ModuleModel {

	protected string $module = 'farm\Supplier';
	protected string $package = 'farm';
	protected string $table = 'farmSupplier';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'collate' => 'general', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'createdAt' => ['date', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'farm', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('CURDATE()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): SupplierModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SupplierModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SupplierModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): SupplierModel {
		return $this->where('name', ...$data);
	}

	public function whereFarm(...$data): SupplierModel {
		return $this->where('farm', ...$data);
	}

	public function whereCreatedAt(...$data): SupplierModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class SupplierCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Supplier {

		$e = new Supplier();

		if(empty($id)) {
			Supplier::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Supplier::getSelection();
		}

		if(Supplier::model()
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
			$properties = Supplier::getSelection();
		}

		if($sort !== NULL) {
			Supplier::model()->sort($sort);
		}

		return Supplier::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Supplier {

		return new Supplier(['id' => NULL]);

	}

	public static function create(Supplier $e): void {

		Supplier::model()->insert($e);

	}

	public static function update(Supplier $e, array $properties): void {

		$e->expects(['id']);

		Supplier::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Supplier $e, array $properties): void {

		Supplier::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Supplier $e): void {

		$e->expects(['id']);

		Supplier::model()->delete($e);

	}

}


class SupplierPage extends \ModulePage {

	protected string $module = 'farm\Supplier';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SupplierLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SupplierLib::getPropertiesUpdate()
		);
	}

}
?>