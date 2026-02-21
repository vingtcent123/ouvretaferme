<?php
namespace invoicing;

abstract class InvoiceLineElement extends \Element {

	use \FilterElement;

	private static ?InvoiceLineModel $model = NULL;

	public static function getSelection(): array {
		return InvoiceLine::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): InvoiceLineModel {
		if(self::$model === NULL) {
			self::$model = new InvoiceLineModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('InvoiceLine::'.$failName, $arguments, $wrapper);
	}

}


class InvoiceLineModel extends \ModuleModel {

	protected string $module = 'invoicing\InvoiceLine';
	protected string $package = 'invoicing';
	protected string $table = 'invoicingInvoiceLine';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'invoice' => ['element32', 'invoicing\Invoice', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'invoice'
		]);

		$this->propertiesToModule += [
			'invoice' => 'invoicing\Invoice',
		];

	}

	public function select(...$fields): InvoiceLineModel {
		return parent::select(...$fields);
	}

	public function where(...$data): InvoiceLineModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): InvoiceLineModel {
		return $this->where('id', ...$data);
	}

	public function whereInvoice(...$data): InvoiceLineModel {
		return $this->where('invoice', ...$data);
	}


}


abstract class InvoiceLineCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): InvoiceLine {

		$e = new InvoiceLine();

		if(empty($id)) {
			InvoiceLine::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = InvoiceLine::getSelection();
		}

		if(InvoiceLine::model()
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
			$properties = InvoiceLine::getSelection();
		}

		if($sort !== NULL) {
			InvoiceLine::model()->sort($sort);
		}

		return InvoiceLine::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): InvoiceLine {

		return new InvoiceLine($properties);

	}

	public static function create(InvoiceLine $e): void {

		InvoiceLine::model()->insert($e);

	}

	public static function update(InvoiceLine $e, array $properties): void {

		$e->expects(['id']);

		InvoiceLine::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, InvoiceLine $e, array $properties): void {

		InvoiceLine::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(InvoiceLine $e): void {

		$e->expects(['id']);

		InvoiceLine::model()->delete($e);

	}

}


class InvoiceLinePage extends \ModulePage {

	protected string $module = 'invoicing\InvoiceLine';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? InvoiceLineLib::getPropertiesCreate(),
		   $propertiesUpdate ?? InvoiceLineLib::getPropertiesUpdate()
		);
	}

}
?>