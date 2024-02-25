<?php
namespace hr;

abstract class AbsenceElement extends \Element {

	use \FilterElement;

	private static ?AbsenceModel $model = NULL;

	const VACATION = 'vacation';
	const RTT = 'rtt';
	const RECOVERY = 'recovery';
	const OTHER = 'other';

	public static function getSelection(): array {
		return Absence::model()->getProperties();
	}

	public static function model(): AbsenceModel {
		if(self::$model === NULL) {
			self::$model = new AbsenceModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Absence::'.$failName, $arguments, $wrapper);
	}

}


class AbsenceModel extends \ModuleModel {

	protected string $module = 'hr\Absence';
	protected string $package = 'hr';
	protected string $table = 'hrAbsence';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'from' => ['datetime', 'cast' => 'string'],
			'to' => ['datetime', 'cast' => 'string'],
			'duration' => ['float32', 'min' => 0.0, 'max' => NULL, 'cast' => 'float'],
			'type' => ['enum', [\hr\Absence::VACATION, \hr\Absence::RTT, \hr\Absence::RECOVERY, \hr\Absence::OTHER], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'user', 'from', 'to', 'duration', 'type'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'user' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'user']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): AbsenceModel {
		return parent::select(...$fields);
	}

	public function where(...$data): AbsenceModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): AbsenceModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): AbsenceModel {
		return $this->where('farm', ...$data);
	}

	public function whereUser(...$data): AbsenceModel {
		return $this->where('user', ...$data);
	}

	public function whereFrom(...$data): AbsenceModel {
		return $this->where('from', ...$data);
	}

	public function whereTo(...$data): AbsenceModel {
		return $this->where('to', ...$data);
	}

	public function whereDuration(...$data): AbsenceModel {
		return $this->where('duration', ...$data);
	}

	public function whereType(...$data): AbsenceModel {
		return $this->where('type', ...$data);
	}


}


abstract class AbsenceCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Absence {

		$e = new Absence();

		if(empty($id)) {
			Absence::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Absence::getSelection();
		}

		if(Absence::model()
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
			$properties = Absence::getSelection();
		}

		if($sort !== NULL) {
			Absence::model()->sort($sort);
		}

		return Absence::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Absence {

		return new Absence(['id' => NULL]);

	}

	public static function create(Absence $e): void {

		Absence::model()->insert($e);

	}

	public static function update(Absence $e, array $properties): void {

		$e->expects(['id']);

		Absence::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Absence $e, array $properties): void {

		Absence::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Absence $e): void {

		$e->expects(['id']);

		Absence::model()->delete($e);

	}

}


class AbsencePage extends \ModulePage {

	protected string $module = 'hr\Absence';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? AbsenceLib::getPropertiesCreate(),
		   $propertiesUpdate ?? AbsenceLib::getPropertiesUpdate()
		);
	}

}
?>