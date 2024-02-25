<?php
namespace series;

abstract class TimesheetElement extends \Element {

	use \FilterElement;

	private static ?TimesheetModel $model = NULL;

	public static function getSelection(): array {
		return Timesheet::model()->getProperties();
	}

	public static function model(): TimesheetModel {
		if(self::$model === NULL) {
			self::$model = new TimesheetModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Timesheet::'.$failName, $arguments, $wrapper);
	}

}


class TimesheetModel extends \ModuleModel {

	protected string $module = 'series\Timesheet';
	protected string $package = 'series';
	protected string $table = 'seriesTimesheet';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'series' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'cultivation' => ['element32', 'series\Cultivation', 'null' => TRUE, 'cast' => 'element'],
			'plant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'task' => ['element32', 'series\Task', 'cast' => 'element'],
			'time' => ['float32', 'min' => 0.0, 'max' => NULL, 'cast' => 'float'],
			'date' => ['date', 'cast' => 'string'],
			'week' => ['week', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'user', 'series', 'cultivation', 'plant', 'task', 'time', 'date', 'week', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'user' => 'user\User',
			'series' => 'series\Series',
			'cultivation' => 'series\Cultivation',
			'plant' => 'plant\Plant',
			'task' => 'series\Task',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'date'],
			['user', 'date', 'task'],
			['task'],
			['series']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'time' :
				return 0;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): TimesheetModel {
		return parent::select(...$fields);
	}

	public function where(...$data): TimesheetModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): TimesheetModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): TimesheetModel {
		return $this->where('farm', ...$data);
	}

	public function whereUser(...$data): TimesheetModel {
		return $this->where('user', ...$data);
	}

	public function whereSeries(...$data): TimesheetModel {
		return $this->where('series', ...$data);
	}

	public function whereCultivation(...$data): TimesheetModel {
		return $this->where('cultivation', ...$data);
	}

	public function wherePlant(...$data): TimesheetModel {
		return $this->where('plant', ...$data);
	}

	public function whereTask(...$data): TimesheetModel {
		return $this->where('task', ...$data);
	}

	public function whereTime(...$data): TimesheetModel {
		return $this->where('time', ...$data);
	}

	public function whereDate(...$data): TimesheetModel {
		return $this->where('date', ...$data);
	}

	public function whereWeek(...$data): TimesheetModel {
		return $this->where('week', ...$data);
	}

	public function whereCreatedAt(...$data): TimesheetModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): TimesheetModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class TimesheetCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Timesheet {

		$e = new Timesheet();

		if(empty($id)) {
			Timesheet::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Timesheet::getSelection();
		}

		if(Timesheet::model()
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
			$properties = Timesheet::getSelection();
		}

		if($sort !== NULL) {
			Timesheet::model()->sort($sort);
		}

		return Timesheet::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Timesheet {

		return new Timesheet(['id' => NULL]);

	}

	public static function create(Timesheet $e): void {

		Timesheet::model()->insert($e);

	}

	public static function update(Timesheet $e, array $properties): void {

		$e->expects(['id']);

		Timesheet::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Timesheet $e, array $properties): void {

		Timesheet::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Timesheet $e): void {

		$e->expects(['id']);

		Timesheet::model()->delete($e);

	}

}


class TimesheetPage extends \ModulePage {

	protected string $module = 'series\Timesheet';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? TimesheetLib::getPropertiesCreate(),
		   $propertiesUpdate ?? TimesheetLib::getPropertiesUpdate()
		);
	}

}
?>