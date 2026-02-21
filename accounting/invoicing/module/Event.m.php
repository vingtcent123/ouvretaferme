<?php
namespace invoicing;

abstract class EventElement extends \Element {

	use \FilterElement;

	private static ?EventModel $model = NULL;

	public static function getSelection(): array {
		return Event::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'invoicing\Event';
	protected string $package = 'invoicing';
	protected string $table = 'invoicingEvent';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'invoice' => ['element32', 'invoicing\Invoice', 'cast' => 'element'],
			'statusCode' => ['text8', 'charset' => 'ascii', 'cast' => 'string'],
			'statusText' => ['text8', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'invoice', 'statusCode', 'statusText', 'createdAt'
		]);

		$this->propertiesToModule += [
			'invoice' => 'invoicing\Invoice',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['invoice']
		]);

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

	public function whereInvoice(...$data): EventModel {
		return $this->where('invoice', ...$data);
	}

	public function whereStatusCode(...$data): EventModel {
		return $this->where('statusCode', ...$data);
	}

	public function whereStatusText(...$data): EventModel {
		return $this->where('statusText', ...$data);
	}

	public function whereCreatedAt(...$data): EventModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class EventCrud extends \ModuleCrud {

 private static array $cache = [];

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

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Event {

		return new Event($properties);

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

	protected string $module = 'invoicing\Event';

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