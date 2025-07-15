<?php
namespace journal;

abstract class DeferredChargeElement extends \Element {

	use \FilterElement;

	private static ?DeferredChargeModel $model = NULL;

	const PLANNED = 'planned';
	const RECORDED = 'recorded';
	const DEFERRED = 'deferred';
	const CANCELLED = 'cancelled';

	public static function getSelection(): array {
		return DeferredCharge::model()->getProperties();
	}

	public static function model(): DeferredChargeModel {
		if(self::$model === NULL) {
			self::$model = new DeferredChargeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('DeferredCharge::'.$failName, $arguments, $wrapper);
	}

}


class DeferredChargeModel extends \ModuleModel {

	protected string $module = 'journal\DeferredCharge';
	protected string $package = 'journal';
	protected string $table = 'journalDeferredCharge';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'operation' => ['element32', 'journal\Operation', 'cast' => 'element'],
			'startDate' => ['date', 'cast' => 'string'],
			'endDate' => ['date', 'cast' => 'string'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.01, 'max' => NULL, 'cast' => 'float'],
			'initialFinancialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'destinationFinancialYear' => ['element32', 'account\FinancialYear', 'null' => TRUE, 'cast' => 'element'],
			'status' => ['enum', [\journal\DeferredCharge::PLANNED, \journal\DeferredCharge::RECORDED, \journal\DeferredCharge::DEFERRED, \journal\DeferredCharge::CANCELLED], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'operation', 'startDate', 'endDate', 'amount', 'initialFinancialYear', 'destinationFinancialYear', 'status', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'operation' => 'journal\Operation',
			'initialFinancialYear' => 'account\FinancialYear',
			'destinationFinancialYear' => 'account\FinancialYear',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['initialFinancialYear'],
			['status']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): DeferredChargeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DeferredChargeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DeferredChargeModel {
		return $this->where('id', ...$data);
	}

	public function whereOperation(...$data): DeferredChargeModel {
		return $this->where('operation', ...$data);
	}

	public function whereStartDate(...$data): DeferredChargeModel {
		return $this->where('startDate', ...$data);
	}

	public function whereEndDate(...$data): DeferredChargeModel {
		return $this->where('endDate', ...$data);
	}

	public function whereAmount(...$data): DeferredChargeModel {
		return $this->where('amount', ...$data);
	}

	public function whereInitialFinancialYear(...$data): DeferredChargeModel {
		return $this->where('initialFinancialYear', ...$data);
	}

	public function whereDestinationFinancialYear(...$data): DeferredChargeModel {
		return $this->where('destinationFinancialYear', ...$data);
	}

	public function whereStatus(...$data): DeferredChargeModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): DeferredChargeModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): DeferredChargeModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereCreatedBy(...$data): DeferredChargeModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class DeferredChargeCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): DeferredCharge {

		$e = new DeferredCharge();

		if(empty($id)) {
			DeferredCharge::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = DeferredCharge::getSelection();
		}

		if(DeferredCharge::model()
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
			$properties = DeferredCharge::getSelection();
		}

		if($sort !== NULL) {
			DeferredCharge::model()->sort($sort);
		}

		return DeferredCharge::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): DeferredCharge {

		return new DeferredCharge(['id' => NULL]);

	}

	public static function create(DeferredCharge $e): void {

		DeferredCharge::model()->insert($e);

	}

	public static function update(DeferredCharge $e, array $properties): void {

		$e->expects(['id']);

		DeferredCharge::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, DeferredCharge $e, array $properties): void {

		DeferredCharge::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(DeferredCharge $e): void {

		$e->expects(['id']);

		DeferredCharge::model()->delete($e);

	}

}


class DeferredChargePage extends \ModulePage {

	protected string $module = 'journal\DeferredCharge';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DeferredChargeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DeferredChargeLib::getPropertiesUpdate()
		);
	}

}
?>