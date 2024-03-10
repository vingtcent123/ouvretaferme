<?php
namespace series;

abstract class TaskElement extends \Element {

	use \FilterElement;

	private static ?TaskModel $model = NULL;

	const KG = 'kg';
	const UNIT = 'unit';
	const BUNCH = 'bunch';

	const TODO = 'todo';
	const DONE = 'done';

	public static function getSelection(): array {
		return Task::model()->getProperties();
	}

	public static function model(): TaskModel {
		if(self::$model === NULL) {
			self::$model = new TaskModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Task::'.$failName, $arguments, $wrapper);
	}

}


class TaskModel extends \ModuleModel {

	protected string $module = 'series\Task';
	protected string $package = 'series';
	protected string $table = 'seriesTask';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'cultivation' => ['element32', 'series\Cultivation', 'null' => TRUE, 'cast' => 'element'],
			'series' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'plant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'variety' => ['element32', 'plant\Variety', 'null' => TRUE, 'cast' => 'element'],
			'action' => ['element32', 'farm\Action', 'cast' => 'element'],
			'category' => ['element32', 'farm\Category', 'cast' => 'element'],
			'description' => ['text16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'time' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'timeExpected' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'harvest' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'harvestUnit' => ['enum', [\series\Task::KG, \series\Task::UNIT, \series\Task::BUNCH], 'null' => TRUE, 'cast' => 'enum'],
			'harvestQuality' => ['element32', 'plant\Quality', 'null' => TRUE, 'cast' => 'element'],
			'fertilizer' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'plannedWeek' => ['week', 'min' => toWeek('NOW - 2 YEAR'), 'max' => toWeek('NOW + 10 YEAR'), 'null' => TRUE, 'cast' => 'string'],
			'plannedDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'plannedUsers' => ['json', 'cast' => 'array'],
			'doneWeek' => ['week', 'null' => TRUE, 'cast' => 'string'],
			'doneDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'timelineStart' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'timelineStop' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'timesheetStart' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'timesheetStop' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'repeat' => ['element32', 'series\Repeat', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'updatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\series\Task::TODO, \series\Task::DONE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'season', 'cultivation', 'series', 'plant', 'variety', 'action', 'category', 'description', 'time', 'timeExpected', 'harvest', 'harvestUnit', 'harvestQuality', 'fertilizer', 'plannedWeek', 'plannedDate', 'plannedUsers', 'doneWeek', 'doneDate', 'timelineStart', 'timelineStop', 'timesheetStart', 'timesheetStop', 'repeat', 'createdAt', 'createdBy', 'updatedAt', 'status'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'cultivation' => 'series\Cultivation',
			'series' => 'series\Series',
			'plant' => 'plant\Plant',
			'variety' => 'plant\Variety',
			'action' => 'farm\Action',
			'category' => 'farm\Category',
			'harvestQuality' => 'plant\Quality',
			'repeat' => 'series\Repeat',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'status', 'doneWeek'],
			['repeat'],
			['series'],
			['cultivation']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'plannedUsers' :
				return [];

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'harvestUnit' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'fertilizer' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'plannedUsers' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'fertilizer' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'plannedUsers' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): TaskModel {
		return parent::select(...$fields);
	}

	public function where(...$data): TaskModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): TaskModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): TaskModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeason(...$data): TaskModel {
		return $this->where('season', ...$data);
	}

	public function whereCultivation(...$data): TaskModel {
		return $this->where('cultivation', ...$data);
	}

	public function whereSeries(...$data): TaskModel {
		return $this->where('series', ...$data);
	}

	public function wherePlant(...$data): TaskModel {
		return $this->where('plant', ...$data);
	}

	public function whereVariety(...$data): TaskModel {
		return $this->where('variety', ...$data);
	}

	public function whereAction(...$data): TaskModel {
		return $this->where('action', ...$data);
	}

	public function whereCategory(...$data): TaskModel {
		return $this->where('category', ...$data);
	}

	public function whereDescription(...$data): TaskModel {
		return $this->where('description', ...$data);
	}

	public function whereTime(...$data): TaskModel {
		return $this->where('time', ...$data);
	}

	public function whereTimeExpected(...$data): TaskModel {
		return $this->where('timeExpected', ...$data);
	}

	public function whereHarvest(...$data): TaskModel {
		return $this->where('harvest', ...$data);
	}

	public function whereHarvestUnit(...$data): TaskModel {
		return $this->where('harvestUnit', ...$data);
	}

	public function whereHarvestQuality(...$data): TaskModel {
		return $this->where('harvestQuality', ...$data);
	}

	public function whereFertilizer(...$data): TaskModel {
		return $this->where('fertilizer', ...$data);
	}

	public function wherePlannedWeek(...$data): TaskModel {
		return $this->where('plannedWeek', ...$data);
	}

	public function wherePlannedDate(...$data): TaskModel {
		return $this->where('plannedDate', ...$data);
	}

	public function wherePlannedUsers(...$data): TaskModel {
		return $this->where('plannedUsers', ...$data);
	}

	public function whereDoneWeek(...$data): TaskModel {
		return $this->where('doneWeek', ...$data);
	}

	public function whereDoneDate(...$data): TaskModel {
		return $this->where('doneDate', ...$data);
	}

	public function whereTimelineStart(...$data): TaskModel {
		return $this->where('timelineStart', ...$data);
	}

	public function whereTimelineStop(...$data): TaskModel {
		return $this->where('timelineStop', ...$data);
	}

	public function whereTimesheetStart(...$data): TaskModel {
		return $this->where('timesheetStart', ...$data);
	}

	public function whereTimesheetStop(...$data): TaskModel {
		return $this->where('timesheetStop', ...$data);
	}

	public function whereRepeat(...$data): TaskModel {
		return $this->where('repeat', ...$data);
	}

	public function whereCreatedAt(...$data): TaskModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): TaskModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereUpdatedAt(...$data): TaskModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereStatus(...$data): TaskModel {
		return $this->where('status', ...$data);
	}


}


abstract class TaskCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Task {

		$e = new Task();

		if(empty($id)) {
			Task::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Task::getSelection();
		}

		if(Task::model()
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
			$properties = Task::getSelection();
		}

		if($sort !== NULL) {
			Task::model()->sort($sort);
		}

		return Task::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Task {

		return new Task(['id' => NULL]);

	}

	public static function create(Task $e): void {

		Task::model()->insert($e);

	}

	public static function update(Task $e, array $properties): void {

		$e->expects(['id']);

		Task::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Task $e, array $properties): void {

		Task::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Task $e): void {

		$e->expects(['id']);

		Task::model()->delete($e);

	}

}


class TaskPage extends \ModulePage {

	protected string $module = 'series\Task';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? TaskLib::getPropertiesCreate(),
		   $propertiesUpdate ?? TaskLib::getPropertiesUpdate()
		);
	}

}
?>