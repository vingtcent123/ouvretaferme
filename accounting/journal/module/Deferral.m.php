<?php
namespace journal;

abstract class DeferralElement extends \Element {

	use \FilterElement;

	private static ?DeferralModel $model = NULL;

	const CHARGE = 'charge';
	const PRODUCT = 'product';

	const PLANNED = 'planned';
	const RECORDED = 'recorded';
	const DEFERRED = 'deferred';
	const CANCELLED = 'cancelled';

	public static function getSelection(): array {
		return Deferral::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): DeferralModel {
		if(self::$model === NULL) {
			self::$model = new DeferralModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Deferral::'.$failName, $arguments, $wrapper);
	}

}


class DeferralModel extends \ModuleModel {

	protected string $module = 'journal\Deferral';
	protected string $package = 'journal';
	protected string $table = 'journalDeferral';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'type' => ['enum', [\journal\Deferral::CHARGE, \journal\Deferral::PRODUCT], 'cast' => 'enum'],
			'operation' => ['element32', 'journal\Operation', 'cast' => 'element'],
			'startDate' => ['date', 'cast' => 'string'],
			'endDate' => ['date', 'cast' => 'string'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.01, 'max' => 999999.99, 'cast' => 'float'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'status' => ['enum', [\journal\Deferral::PLANNED, \journal\Deferral::RECORDED, \journal\Deferral::DEFERRED, \journal\Deferral::CANCELLED], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'type', 'operation', 'startDate', 'endDate', 'amount', 'financialYear', 'status', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'operation' => 'journal\Operation',
			'financialYear' => 'account\FinancialYear',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['financialYear'],
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

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): DeferralModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DeferralModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DeferralModel {
		return $this->where('id', ...$data);
	}

	public function whereType(...$data): DeferralModel {
		return $this->where('type', ...$data);
	}

	public function whereOperation(...$data): DeferralModel {
		return $this->where('operation', ...$data);
	}

	public function whereStartDate(...$data): DeferralModel {
		return $this->where('startDate', ...$data);
	}

	public function whereEndDate(...$data): DeferralModel {
		return $this->where('endDate', ...$data);
	}

	public function whereAmount(...$data): DeferralModel {
		return $this->where('amount', ...$data);
	}

	public function whereFinancialYear(...$data): DeferralModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereStatus(...$data): DeferralModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): DeferralModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): DeferralModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereCreatedBy(...$data): DeferralModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class DeferralCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Deferral {

		$e = new Deferral();

		if(empty($id)) {
			Deferral::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Deferral::getSelection();
		}

		if(Deferral::model()
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
			$properties = Deferral::getSelection();
		}

		if($sort !== NULL) {
			Deferral::model()->sort($sort);
		}

		return Deferral::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Deferral {

		return new Deferral(['id' => NULL]);

	}

	public static function create(Deferral $e): void {

		Deferral::model()->insert($e);

	}

	public static function update(Deferral $e, array $properties): void {

		$e->expects(['id']);

		Deferral::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Deferral $e, array $properties): void {

		Deferral::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Deferral $e): void {

		$e->expects(['id']);

		Deferral::model()->delete($e);

	}

}


class DeferralPage extends \ModulePage {

	protected string $module = 'journal\Deferral';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DeferralLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DeferralLib::getPropertiesUpdate()
		);
	}

}
?>