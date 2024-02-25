<?php
namespace series;

abstract class RequirementElement extends \Element {

	use \FilterElement;

	private static ?RequirementModel $model = NULL;

	public static function getSelection(): array {
		return Requirement::model()->getProperties();
	}

	public static function model(): RequirementModel {
		if(self::$model === NULL) {
			self::$model = new RequirementModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Requirement::'.$failName, $arguments, $wrapper);
	}

}


class RequirementModel extends \ModuleModel {

	protected string $module = 'series\Requirement';
	protected string $package = 'series';
	protected string $table = 'seriesRequirement';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'series' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'cultivation' => ['element32', 'series\Cultivation', 'null' => TRUE, 'cast' => 'element'],
			'task' => ['element32', 'series\Task', 'cast' => 'element'],
			'tool' => ['element32', 'farm\Tool', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'series', 'cultivation', 'task', 'tool', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'series' => 'series\Series',
			'cultivation' => 'series\Cultivation',
			'task' => 'series\Task',
			'tool' => 'farm\Tool',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'tool'],
			['series']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['task', 'tool']
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

	public function select(...$fields): RequirementModel {
		return parent::select(...$fields);
	}

	public function where(...$data): RequirementModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): RequirementModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): RequirementModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeries(...$data): RequirementModel {
		return $this->where('series', ...$data);
	}

	public function whereCultivation(...$data): RequirementModel {
		return $this->where('cultivation', ...$data);
	}

	public function whereTask(...$data): RequirementModel {
		return $this->where('task', ...$data);
	}

	public function whereTool(...$data): RequirementModel {
		return $this->where('tool', ...$data);
	}

	public function whereCreatedAt(...$data): RequirementModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): RequirementModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class RequirementCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Requirement {

		$e = new Requirement();

		if(empty($id)) {
			Requirement::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Requirement::getSelection();
		}

		if(Requirement::model()
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
			$properties = Requirement::getSelection();
		}

		if($sort !== NULL) {
			Requirement::model()->sort($sort);
		}

		return Requirement::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Requirement {

		return new Requirement(['id' => NULL]);

	}

	public static function create(Requirement $e): void {

		Requirement::model()->insert($e);

	}

	public static function update(Requirement $e, array $properties): void {

		$e->expects(['id']);

		Requirement::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Requirement $e, array $properties): void {

		Requirement::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Requirement $e): void {

		$e->expects(['id']);

		Requirement::model()->delete($e);

	}

}


class RequirementPage extends \ModulePage {

	protected string $module = 'series\Requirement';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? RequirementLib::getPropertiesCreate(),
		   $propertiesUpdate ?? RequirementLib::getPropertiesUpdate()
		);
	}

}
?>