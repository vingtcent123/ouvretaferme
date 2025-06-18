<?php
namespace company;

abstract class SubscriptionHistoryElement extends \Element {

	use \FilterElement;

	private static ?SubscriptionHistoryModel $model = NULL;

	const ACCOUNTING = 'accounting';
	const PRODUCTION = 'production';
	const SALES = 'sales';

	public static function getSelection(): array {
		return SubscriptionHistory::model()->getProperties();
	}

	public static function model(): SubscriptionHistoryModel {
		if(self::$model === NULL) {
			self::$model = new SubscriptionHistoryModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('SubscriptionHistory::'.$failName, $arguments, $wrapper);
	}

}


class SubscriptionHistoryModel extends \ModuleModel {

	protected string $module = 'company\SubscriptionHistory';
	protected string $package = 'company';
	protected string $table = 'companySubscriptionHistory';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'company' => ['element32', 'company\Company', 'cast' => 'element'],
			'type' => ['enum', [\company\SubscriptionHistory::ACCOUNTING, \company\SubscriptionHistory::PRODUCTION, \company\SubscriptionHistory::SALES], 'cast' => 'enum'],
			'isPack' => ['bool', 'cast' => 'bool'],
			'isBio' => ['bool', 'cast' => 'bool'],
			'startsAt' => ['date', 'cast' => 'string'],
			'endsAt' => ['date', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'company', 'type', 'isPack', 'isBio', 'startsAt', 'endsAt', 'createdBy', 'createdAt'
		]);

		$this->propertiesToModule += [
			'company' => 'company\Company',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['company']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): SubscriptionHistoryModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SubscriptionHistoryModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SubscriptionHistoryModel {
		return $this->where('id', ...$data);
	}

	public function whereCompany(...$data): SubscriptionHistoryModel {
		return $this->where('company', ...$data);
	}

	public function whereType(...$data): SubscriptionHistoryModel {
		return $this->where('type', ...$data);
	}

	public function whereIsPack(...$data): SubscriptionHistoryModel {
		return $this->where('isPack', ...$data);
	}

	public function whereIsBio(...$data): SubscriptionHistoryModel {
		return $this->where('isBio', ...$data);
	}

	public function whereStartsAt(...$data): SubscriptionHistoryModel {
		return $this->where('startsAt', ...$data);
	}

	public function whereEndsAt(...$data): SubscriptionHistoryModel {
		return $this->where('endsAt', ...$data);
	}

	public function whereCreatedBy(...$data): SubscriptionHistoryModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereCreatedAt(...$data): SubscriptionHistoryModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class SubscriptionHistoryCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): SubscriptionHistory {

		$e = new SubscriptionHistory();

		if(empty($id)) {
			SubscriptionHistory::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = SubscriptionHistory::getSelection();
		}

		if(SubscriptionHistory::model()
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
			$properties = SubscriptionHistory::getSelection();
		}

		if($sort !== NULL) {
			SubscriptionHistory::model()->sort($sort);
		}

		return SubscriptionHistory::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): SubscriptionHistory {

		return new SubscriptionHistory(['id' => NULL]);

	}

	public static function create(SubscriptionHistory $e): void {

		SubscriptionHistory::model()->insert($e);

	}

	public static function update(SubscriptionHistory $e, array $properties): void {

		$e->expects(['id']);

		SubscriptionHistory::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, SubscriptionHistory $e, array $properties): void {

		SubscriptionHistory::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(SubscriptionHistory $e): void {

		$e->expects(['id']);

		SubscriptionHistory::model()->delete($e);

	}

}


class SubscriptionHistoryPage extends \ModulePage {

	protected string $module = 'company\SubscriptionHistory';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SubscriptionHistoryLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SubscriptionHistoryLib::getPropertiesUpdate()
		);
	}

}
?>