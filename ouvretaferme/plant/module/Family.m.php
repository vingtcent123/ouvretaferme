<?php
namespace plant;

abstract class FamilyElement extends \Element {

	use \FilterElement;

	private static ?FamilyModel $model = NULL;

	public static function getSelection(): array {
		return Family::model()->getProperties();
	}

	public static function model(): FamilyModel {
		if(self::$model === NULL) {
			self::$model = new FamilyModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Family::'.$failName, $arguments, $wrapper);
	}

}


class FamilyModel extends \ModuleModel {

	protected string $module = 'plant\Family';
	protected string $package = 'plant';
	protected string $table = 'plantFamily';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'fqn' => ['fqn', 'unique' => TRUE, 'cast' => 'string'],
			'color' => ['color', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'fqn', 'color'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fqn']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'color' :
				return '#000000';

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): FamilyModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FamilyModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): FamilyModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): FamilyModel {
		return $this->where('name', ...$data);
	}

	public function whereFqn(...$data): FamilyModel {
		return $this->where('fqn', ...$data);
	}

	public function whereColor(...$data): FamilyModel {
		return $this->where('color', ...$data);
	}


}


abstract class FamilyCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Family {

		$e = new Family();

		if(empty($id)) {
			Family::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Family::getSelection();
		}

		if(Family::model()
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
			$properties = Family::getSelection();
		}

		if($sort !== NULL) {
			Family::model()->sort($sort);
		}

		return Family::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Family {

		$e = new Family();

		if(empty($fqn)) {
			Family::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Family::getSelection();
		}

		if(Family::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Family::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Family::getSelection();
		}

		return Family::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCreateElement(): Family {

		return new Family(['id' => NULL]);

	}

	public static function create(Family $e): void {

		Family::model()->insert($e);

	}

	public static function update(Family $e, array $properties): void {

		$e->expects(['id']);

		Family::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Family $e, array $properties): void {

		Family::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Family $e): void {

		$e->expects(['id']);

		Family::model()->delete($e);

	}

}


class FamilyPage extends \ModulePage {

	protected string $module = 'plant\Family';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FamilyLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FamilyLib::getPropertiesUpdate()
		);
	}

}
?>