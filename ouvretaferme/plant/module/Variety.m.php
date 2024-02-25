<?php
namespace plant;

abstract class VarietyElement extends \Element {

	use \FilterElement;

	private static ?VarietyModel $model = NULL;

	public static function getSelection(): array {
		return Variety::model()->getProperties();
	}

	public static function model(): VarietyModel {
		if(self::$model === NULL) {
			self::$model = new VarietyModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Variety::'.$failName, $arguments, $wrapper);
	}

}


class VarietyModel extends \ModuleModel {

	protected string $module = 'plant\Variety';
	protected string $package = 'plant';
	protected string $table = 'plantVariety';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'collate' => 'general', 'cast' => 'string'],
			'fqn' => ['fqn', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'string'],
			'plant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'supplierSeed' => ['element32', 'farm\Supplier', 'null' => TRUE, 'cast' => 'element'],
			'supplierPlant' => ['element32', 'farm\Supplier', 'null' => TRUE, 'cast' => 'element'],
			'weightSeed1000' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'numberPlantKilogram' => ['int32', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['date', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'fqn', 'plant', 'farm', 'supplierSeed', 'supplierPlant', 'weightSeed1000', 'numberPlantKilogram', 'createdAt'
		]);

		$this->propertiesToModule += [
			'plant' => 'plant\Plant',
			'farm' => 'farm\Farm',
			'supplierSeed' => 'farm\Supplier',
			'supplierPlant' => 'farm\Supplier',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['plant']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fqn'],
			['farm', 'plant', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('CURDATE()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): VarietyModel {
		return parent::select(...$fields);
	}

	public function where(...$data): VarietyModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): VarietyModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): VarietyModel {
		return $this->where('name', ...$data);
	}

	public function whereFqn(...$data): VarietyModel {
		return $this->where('fqn', ...$data);
	}

	public function wherePlant(...$data): VarietyModel {
		return $this->where('plant', ...$data);
	}

	public function whereFarm(...$data): VarietyModel {
		return $this->where('farm', ...$data);
	}

	public function whereSupplierSeed(...$data): VarietyModel {
		return $this->where('supplierSeed', ...$data);
	}

	public function whereSupplierPlant(...$data): VarietyModel {
		return $this->where('supplierPlant', ...$data);
	}

	public function whereWeightSeed1000(...$data): VarietyModel {
		return $this->where('weightSeed1000', ...$data);
	}

	public function whereNumberPlantKilogram(...$data): VarietyModel {
		return $this->where('numberPlantKilogram', ...$data);
	}

	public function whereCreatedAt(...$data): VarietyModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class VarietyCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Variety {

		$e = new Variety();

		if(empty($id)) {
			Variety::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Variety::getSelection();
		}

		if(Variety::model()
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
			$properties = Variety::getSelection();
		}

		if($sort !== NULL) {
			Variety::model()->sort($sort);
		}

		return Variety::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Variety {

		$e = new Variety();

		if(empty($fqn)) {
			Variety::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Variety::getSelection();
		}

		if(Variety::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Variety::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Variety::getSelection();
		}

		return Variety::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCreateElement(): Variety {

		return new Variety(['id' => NULL]);

	}

	public static function create(Variety $e): void {

		Variety::model()->insert($e);

	}

	public static function update(Variety $e, array $properties): void {

		$e->expects(['id']);

		Variety::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Variety $e, array $properties): void {

		Variety::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Variety $e): void {

		$e->expects(['id']);

		Variety::model()->delete($e);

	}

}


class VarietyPage extends \ModulePage {

	protected string $module = 'plant\Variety';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? VarietyLib::getPropertiesCreate(),
		   $propertiesUpdate ?? VarietyLib::getPropertiesUpdate()
		);
	}

}
?>