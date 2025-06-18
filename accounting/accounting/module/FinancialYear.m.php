<?php
namespace accounting;

abstract class FinancialYearElement extends \Element {

	use \FilterElement;

	private static ?FinancialYearModel $model = NULL;

	const OPEN = 'open';
	const CLOSE = 'close';

	public static function getSelection(): array {
		return FinancialYear::model()->getProperties();
	}

	public static function model(): FinancialYearModel {
		if(self::$model === NULL) {
			self::$model = new FinancialYearModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('FinancialYear::'.$failName, $arguments, $wrapper);
	}

}


class FinancialYearModel extends \ModuleModel {

	protected string $module = 'accounting\FinancialYear';
	protected string $package = 'accounting';
	protected string $table = 'accountingFinancialYear';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'startDate' => ['date', 'cast' => 'string'],
			'endDate' => ['date', 'cast' => 'string'],
			'status' => ['enum', [\accounting\FinancialYear::OPEN, \accounting\FinancialYear::CLOSE], 'cast' => 'enum'],
			'balanceSheetOpen' => ['bool', 'cast' => 'bool'],
			'balanceSheetClose' => ['bool', 'cast' => 'bool'],
			'closeDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'startDate', 'endDate', 'status', 'balanceSheetOpen', 'balanceSheetClose', 'closeDate', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'createdBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return FinancialYear::OPEN;

			case 'balanceSheetOpen' :
				return FALSE;

			case 'balanceSheetClose' :
				return FALSE;

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

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): FinancialYearModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FinancialYearModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): FinancialYearModel {
		return $this->where('id', ...$data);
	}

	public function whereStartDate(...$data): FinancialYearModel {
		return $this->where('startDate', ...$data);
	}

	public function whereEndDate(...$data): FinancialYearModel {
		return $this->where('endDate', ...$data);
	}

	public function whereStatus(...$data): FinancialYearModel {
		return $this->where('status', ...$data);
	}

	public function whereBalanceSheetOpen(...$data): FinancialYearModel {
		return $this->where('balanceSheetOpen', ...$data);
	}

	public function whereBalanceSheetClose(...$data): FinancialYearModel {
		return $this->where('balanceSheetClose', ...$data);
	}

	public function whereCloseDate(...$data): FinancialYearModel {
		return $this->where('closeDate', ...$data);
	}

	public function whereCreatedAt(...$data): FinancialYearModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): FinancialYearModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class FinancialYearCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): FinancialYear {

		$e = new FinancialYear();

		if(empty($id)) {
			FinancialYear::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = FinancialYear::getSelection();
		}

		if(FinancialYear::model()
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
			$properties = FinancialYear::getSelection();
		}

		if($sort !== NULL) {
			FinancialYear::model()->sort($sort);
		}

		return FinancialYear::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): FinancialYear {

		return new FinancialYear(['id' => NULL]);

	}

	public static function create(FinancialYear $e): void {

		FinancialYear::model()->insert($e);

	}

	public static function update(FinancialYear $e, array $properties): void {

		$e->expects(['id']);

		FinancialYear::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, FinancialYear $e, array $properties): void {

		FinancialYear::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(FinancialYear $e): void {

		$e->expects(['id']);

		FinancialYear::model()->delete($e);

	}

}


class FinancialYearPage extends \ModulePage {

	protected string $module = 'accounting\FinancialYear';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FinancialYearLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FinancialYearLib::getPropertiesUpdate()
		);
	}

}
?>