<?php
namespace association;

abstract class HistoryElement extends \Element {

	use \FilterElement;

	private static ?HistoryModel $model = NULL;

	const MEMBERSHIP = 'membership';
	const DONATION = 'donation';

	const INITIALIZED = 'initialized';
	const SUCCESS = 'success';
	const FAILURE = 'failure';
	const EXPIRED = 'expired';

	const PROCESSING = 'processing';
	const VALID = 'valid';
	const INVALID = 'invalid';

	public static function getSelection(): array {
		return History::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'association\History';
	protected string $package = 'association';
	protected string $table = 'associationHistory';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'type' => ['enum', [\association\History::MEMBERSHIP, \association\History::DONATION], 'cast' => 'enum'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.01, 'max' => 999999.99, 'cast' => 'float'],
			'membership' => ['int32', 'min' => 2025, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'checkoutId' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'paymentIntentId' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'paymentStatus' => ['enum', [\association\History::INITIALIZED, \association\History::SUCCESS, \association\History::FAILURE, \association\History::EXPIRED], 'null' => TRUE, 'cast' => 'enum'],
			'status' => ['enum', [\association\History::PROCESSING, \association\History::VALID, \association\History::INVALID], 'cast' => 'enum'],
			'sale' => ['element32', 'selling\Sale', 'null' => TRUE, 'cast' => 'element'],
			'document' => ['textFixed', 'min' => 20, 'max' => 20, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'paidAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'customer', 'type', 'amount', 'membership', 'checkoutId', 'paymentIntentId', 'paymentStatus', 'status', 'sale', 'document', 'createdAt', 'updatedAt', 'paidAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'customer' => 'selling\Customer',
			'sale' => 'selling\Sale',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return History::PROCESSING;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'paymentStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

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

	public function whereFarm(...$data): HistoryModel {
		return $this->where('farm', ...$data);
	}

	public function whereCustomer(...$data): HistoryModel {
		return $this->where('customer', ...$data);
	}

	public function whereType(...$data): HistoryModel {
		return $this->where('type', ...$data);
	}

	public function whereAmount(...$data): HistoryModel {
		return $this->where('amount', ...$data);
	}

	public function whereMembership(...$data): HistoryModel {
		return $this->where('membership', ...$data);
	}

	public function whereCheckoutId(...$data): HistoryModel {
		return $this->where('checkoutId', ...$data);
	}

	public function wherePaymentIntentId(...$data): HistoryModel {
		return $this->where('paymentIntentId', ...$data);
	}

	public function wherePaymentStatus(...$data): HistoryModel {
		return $this->where('paymentStatus', ...$data);
	}

	public function whereStatus(...$data): HistoryModel {
		return $this->where('status', ...$data);
	}

	public function whereSale(...$data): HistoryModel {
		return $this->where('sale', ...$data);
	}

	public function whereDocument(...$data): HistoryModel {
		return $this->where('document', ...$data);
	}

	public function whereCreatedAt(...$data): HistoryModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): HistoryModel {
		return $this->where('updatedAt', ...$data);
	}

	public function wherePaidAt(...$data): HistoryModel {
		return $this->where('paidAt', ...$data);
	}


}


abstract class HistoryCrud extends \ModuleCrud {

 private static array $cache = [];

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

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

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

	protected string $module = 'association\History';

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