<?php
namespace bank;

abstract class CashflowInvoiceElement extends \Element {

	use \FilterElement;

	private static ?CashflowInvoiceModel $model = NULL;

	public static function getSelection(): array {
		return CashflowInvoice::model()->getProperties();
	}

	public static function model(): CashflowInvoiceModel {
		if(self::$model === NULL) {
			self::$model = new CashflowInvoiceModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('CashflowInvoice::'.$failName, $arguments, $wrapper);
	}

}


class CashflowInvoiceModel extends \ModuleModel {

	protected string $module = 'bank\CashflowInvoice';
	protected string $package = 'bank';
	protected string $table = 'bankCashflowInvoice';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'cashflow' => ['element32', 'bank\Cashflow', 'cast' => 'element'],
			'invoice' => ['element32', 'selling\Invoice', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'cashflow', 'invoice', 'createdAt'
		]);

		$this->propertiesToModule += [
			'cashflow' => 'bank\Cashflow',
			'invoice' => 'selling\Invoice',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['cashflow', 'invoice']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): CashflowInvoiceModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CashflowInvoiceModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CashflowInvoiceModel {
		return $this->where('id', ...$data);
	}

	public function whereCashflow(...$data): CashflowInvoiceModel {
		return $this->where('cashflow', ...$data);
	}

	public function whereInvoice(...$data): CashflowInvoiceModel {
		return $this->where('invoice', ...$data);
	}

	public function whereCreatedAt(...$data): CashflowInvoiceModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class CashflowInvoiceCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): CashflowInvoice {

		$e = new CashflowInvoice();

		if(empty($id)) {
			CashflowInvoice::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = CashflowInvoice::getSelection();
		}

		if(CashflowInvoice::model()
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
			$properties = CashflowInvoice::getSelection();
		}

		if($sort !== NULL) {
			CashflowInvoice::model()->sort($sort);
		}

		return CashflowInvoice::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): CashflowInvoice {

		return new CashflowInvoice(['id' => NULL]);

	}

	public static function create(CashflowInvoice $e): void {

		CashflowInvoice::model()->insert($e);

	}

	public static function update(CashflowInvoice $e, array $properties): void {

		$e->expects(['id']);

		CashflowInvoice::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, CashflowInvoice $e, array $properties): void {

		CashflowInvoice::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(CashflowInvoice $e): void {

		$e->expects(['id']);

		CashflowInvoice::model()->delete($e);

	}

}


class CashflowInvoicePage extends \ModulePage {

	protected string $module = 'bank\CashflowInvoice';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CashflowInvoiceLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CashflowInvoiceLib::getPropertiesUpdate()
		);
	}

}
?>