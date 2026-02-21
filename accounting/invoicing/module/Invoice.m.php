<?php
namespace invoicing;

abstract class InvoiceElement extends \Element {

	use \FilterElement;

	private static ?InvoiceModel $model = NULL;

	const IN = 'in';
	const OUT = 'out';

	public static function getSelection(): array {
		return Invoice::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): InvoiceModel {
		if(self::$model === NULL) {
			self::$model = new InvoiceModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Invoice::'.$failName, $arguments, $wrapper);
	}

}


class InvoiceModel extends \ModuleModel {

	protected string $module = 'invoicing\Invoice';
	protected string $package = 'invoicing';
	protected string $table = 'invoicingInvoice';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'direction' => ['enum', [\invoicing\Invoice::IN, \invoicing\Invoice::OUT], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'synchronizedAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'direction', 'createdAt', 'synchronizedAt'
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'synchronizedAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'direction' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): InvoiceModel {
		return parent::select(...$fields);
	}

	public function where(...$data): InvoiceModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): InvoiceModel {
		return $this->where('id', ...$data);
	}

	public function whereDirection(...$data): InvoiceModel {
		return $this->where('direction', ...$data);
	}

	public function whereCreatedAt(...$data): InvoiceModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereSynchronizedAt(...$data): InvoiceModel {
		return $this->where('synchronizedAt', ...$data);
	}


}


abstract class InvoiceCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Invoice {

		$e = new Invoice();

		if(empty($id)) {
			Invoice::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Invoice::getSelection();
		}

		if(Invoice::model()
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
			$properties = Invoice::getSelection();
		}

		if($sort !== NULL) {
			Invoice::model()->sort($sort);
		}

		return Invoice::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Invoice {

		return new Invoice($properties);

	}

	public static function create(Invoice $e): void {

		Invoice::model()->insert($e);

	}

	public static function update(Invoice $e, array $properties): void {

		$e->expects(['id']);

		Invoice::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Invoice $e, array $properties): void {

		Invoice::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Invoice $e): void {

		$e->expects(['id']);

		Invoice::model()->delete($e);

	}

}


class InvoicePage extends \ModulePage {

	protected string $module = 'invoicing\Invoice';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? InvoiceLib::getPropertiesCreate(),
		   $propertiesUpdate ?? InvoiceLib::getPropertiesUpdate()
		);
	}

}
?>