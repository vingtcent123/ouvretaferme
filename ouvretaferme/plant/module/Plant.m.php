<?php
namespace plant;

abstract class PlantElement extends \Element {

	use \FilterElement;

	private static ?PlantModel $model = NULL;

	const ANNUAL = 'annual';
	const PERENNIAL = 'perennial';

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

	public static function getSelection(): array {
		return Plant::model()->getProperties();
	}

	public static function model(): PlantModel {
		if(self::$model === NULL) {
			self::$model = new PlantModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Plant::'.$failName, $arguments, $wrapper);
	}

}


class PlantModel extends \ModuleModel {

	protected string $module = 'plant\Plant';
	protected string $package = 'plant';
	protected string $table = 'plant';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'fqn' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'aliases' => ['text8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'latinName' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'family' => ['element32', 'plant\Family', 'null' => TRUE, 'cast' => 'element'],
			'vignette' => ['text8', 'min' => 30, 'max' => 30, 'null' => TRUE, 'cast' => 'string'],
			'cycle' => ['enum', [\plant\Plant::ANNUAL, \plant\Plant::PERENNIAL], 'cast' => 'enum'],
			'status' => ['enum', [\plant\Plant::ACTIVE, \plant\Plant::INACTIVE], 'cast' => 'enum'],
			'createdAt' => ['date', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'fqn', 'aliases', 'latinName', 'farm', 'family', 'vignette', 'cycle', 'status', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'family' => 'plant\Family',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['family']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Plant::ACTIVE;

			case 'createdAt' :
				return new \Sql('CURDATE()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'cycle' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): PlantModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PlantModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PlantModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): PlantModel {
		return $this->where('name', ...$data);
	}

	public function whereFqn(...$data): PlantModel {
		return $this->where('fqn', ...$data);
	}

	public function whereAliases(...$data): PlantModel {
		return $this->where('aliases', ...$data);
	}

	public function whereLatinName(...$data): PlantModel {
		return $this->where('latinName', ...$data);
	}

	public function whereFarm(...$data): PlantModel {
		return $this->where('farm', ...$data);
	}

	public function whereFamily(...$data): PlantModel {
		return $this->where('family', ...$data);
	}

	public function whereVignette(...$data): PlantModel {
		return $this->where('vignette', ...$data);
	}

	public function whereCycle(...$data): PlantModel {
		return $this->where('cycle', ...$data);
	}

	public function whereStatus(...$data): PlantModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): PlantModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class PlantCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Plant {

		$e = new Plant();

		if(empty($id)) {
			Plant::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Plant::getSelection();
		}

		if(Plant::model()
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
			$properties = Plant::getSelection();
		}

		if($sort !== NULL) {
			Plant::model()->sort($sort);
		}

		return Plant::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Plant {

		return new Plant(['id' => NULL]);

	}

	public static function create(Plant $e): void {

		Plant::model()->insert($e);

	}

	public static function update(Plant $e, array $properties): void {

		$e->expects(['id']);

		Plant::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Plant $e, array $properties): void {

		Plant::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Plant $e): void {

		$e->expects(['id']);

		Plant::model()->delete($e);

	}

}


class PlantPage extends \ModulePage {

	protected string $module = 'plant\Plant';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PlantLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PlantLib::getPropertiesUpdate()
		);
	}

}
?>