<?php
namespace hr;

abstract class WorkingTimeElement extends \Element {

	use \FilterElement;

	private static ?WorkingTimeModel $model = NULL;

	public static function getSelection(): array {
		return WorkingTime::model()->getProperties();
	}

	public static function model(): WorkingTimeModel {
		if(self::$model === NULL) {
			self::$model = new WorkingTimeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('WorkingTime::'.$failName, $arguments, $wrapper);
	}

}


class WorkingTimeModel extends \ModuleModel {

	protected string $module = 'hr\WorkingTime';
	protected string $package = 'hr';
	protected string $table = 'hrWorkingTime';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'date' => ['date', 'min' => NULL, 'max' => currentDate(), 'cast' => 'string'],
			'time' => ['float32', 'min' => 0.0, 'max' => NULL, 'cast' => 'float'],
			'auto' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'user', 'date', 'time', 'auto', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'user' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'user', 'date']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'auto' :
				return FALSE;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): WorkingTimeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): WorkingTimeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): WorkingTimeModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): WorkingTimeModel {
		return $this->where('farm', ...$data);
	}

	public function whereUser(...$data): WorkingTimeModel {
		return $this->where('user', ...$data);
	}

	public function whereDate(...$data): WorkingTimeModel {
		return $this->where('date', ...$data);
	}

	public function whereTime(...$data): WorkingTimeModel {
		return $this->where('time', ...$data);
	}

	public function whereAuto(...$data): WorkingTimeModel {
		return $this->where('auto', ...$data);
	}

	public function whereCreatedAt(...$data): WorkingTimeModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class WorkingTimeCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): WorkingTime {

		$e = new WorkingTime();

		if(empty($id)) {
			WorkingTime::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = WorkingTime::getSelection();
		}

		if(WorkingTime::model()
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
			$properties = WorkingTime::getSelection();
		}

		if($sort !== NULL) {
			WorkingTime::model()->sort($sort);
		}

		return WorkingTime::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): WorkingTime {

		return new WorkingTime(['id' => NULL]);

	}

	public static function create(WorkingTime $e): void {

		WorkingTime::model()->insert($e);

	}

	public static function update(WorkingTime $e, array $properties): void {

		$e->expects(['id']);

		WorkingTime::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, WorkingTime $e, array $properties): void {

		WorkingTime::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(WorkingTime $e): void {

		$e->expects(['id']);

		WorkingTime::model()->delete($e);

	}

}


class WorkingTimePage extends \ModulePage {

	protected string $module = 'hr\WorkingTime';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? WorkingTimeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? WorkingTimeLib::getPropertiesUpdate()
		);
	}

}
?>