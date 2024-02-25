<?php
namespace selling;

abstract class HistoryElement extends \Element {

	use \FilterElement;

	private static ?HistoryModel $model = NULL;

	public static function getSelection(): array {
		return History::model()->getProperties();
	}

	public static function model(): HistoryModel {
		if(self::$model === NULL) {
			self::$model = new HistoryModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('History::'.$failName, $arguments, $wrapper);
	}

}


class HistoryModel extends \ModuleModel {

	protected string $module = 'selling\History';
	protected string $package = 'selling';
	protected string $table = 'sellingHistory';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'sale' => ['element32', 'selling\Sale', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'event' => ['element32', 'selling\Event', 'cast' => 'element'],
			'payment' => ['element32', 'selling\Payment', 'null' => TRUE, 'cast' => 'element'],
			'comment' => ['text24', 'null' => TRUE, 'cast' => 'string'],
			'date' => ['datetime', 'cast' => 'string'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'sale', 'farm', 'event', 'payment', 'comment', 'date', 'user'
		]);

		$this->propertiesToModule += [
			'sale' => 'selling\Sale',
			'farm' => 'farm\Farm',
			'event' => 'selling\Event',
			'payment' => 'selling\Payment',
			'user' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['sale']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'date' :
				return new \Sql('NOW()');

			case 'user' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): HistoryModel {
		return parent::select(...$fields);
	}

	public function where(...$data): HistoryModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): HistoryModel {
		return $this->where('id', ...$data);
	}

	public function whereSale(...$data): HistoryModel {
		return $this->where('sale', ...$data);
	}

	public function whereFarm(...$data): HistoryModel {
		return $this->where('farm', ...$data);
	}

	public function whereEvent(...$data): HistoryModel {
		return $this->where('event', ...$data);
	}

	public function wherePayment(...$data): HistoryModel {
		return $this->where('payment', ...$data);
	}

	public function whereComment(...$data): HistoryModel {
		return $this->where('comment', ...$data);
	}

	public function whereDate(...$data): HistoryModel {
		return $this->where('date', ...$data);
	}

	public function whereUser(...$data): HistoryModel {
		return $this->where('user', ...$data);
	}


}


abstract class HistoryCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): History {

		$e = new History();

		if(empty($id)) {
			History::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = History::getSelection();
		}

		if(History::model()
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
			$properties = History::getSelection();
		}

		if($sort !== NULL) {
			History::model()->sort($sort);
		}

		return History::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): History {

		return new History(['id' => NULL]);

	}

	public static function create(History $e): void {

		History::model()->insert($e);

	}

	public static function update(History $e, array $properties): void {

		$e->expects(['id']);

		History::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, History $e, array $properties): void {

		History::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(History $e): void {

		$e->expects(['id']);

		History::model()->delete($e);

	}

}


class HistoryPage extends \ModulePage {

	protected string $module = 'selling\History';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? HistoryLib::getPropertiesCreate(),
		   $propertiesUpdate ?? HistoryLib::getPropertiesUpdate()
		);
	}

}
?>