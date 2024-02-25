<?php
namespace selling;

abstract class EventElement extends \Element {

	use \FilterElement;

	private static ?EventModel $model = NULL;

	public static function getSelection(): array {
		return Event::model()->getProperties();
	}

	public static function model(): EventModel {
		if(self::$model === NULL) {
			self::$model = new EventModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Event::'.$failName, $arguments, $wrapper);
	}

}


class EventModel extends \ModuleModel {

	protected string $module = 'selling\Event';
	protected string $package = 'selling';
	protected string $table = 'sellingEvent';

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
				return '#AAAAAA';

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): EventModel {
		return parent::select(...$fields);
	}

	public function where(...$data): EventModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): EventModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): EventModel {
		return $this->where('name', ...$data);
	}

	public function whereFqn(...$data): EventModel {
		return $this->where('fqn', ...$data);
	}

	public function whereColor(...$data): EventModel {
		return $this->where('color', ...$data);
	}


}


abstract class EventCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Event {

		$e = new Event();

		if(empty($id)) {
			Event::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Event::getSelection();
		}

		if(Event::model()
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
			$properties = Event::getSelection();
		}

		if($sort !== NULL) {
			Event::model()->sort($sort);
		}

		return Event::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Event {

		$e = new Event();

		if(empty($fqn)) {
			Event::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Event::getSelection();
		}

		if(Event::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Event::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Event::getSelection();
		}

		return Event::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCreateElement(): Event {

		return new Event(['id' => NULL]);

	}

	public static function create(Event $e): void {

		Event::model()->insert($e);

	}

	public static function update(Event $e, array $properties): void {

		$e->expects(['id']);

		Event::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Event $e, array $properties): void {

		Event::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Event $e): void {

		$e->expects(['id']);

		Event::model()->delete($e);

	}

}


class EventPage extends \ModulePage {

	protected string $module = 'selling\Event';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? EventLib::getPropertiesCreate(),
		   $propertiesUpdate ?? EventLib::getPropertiesUpdate()
		);
	}

}
?>