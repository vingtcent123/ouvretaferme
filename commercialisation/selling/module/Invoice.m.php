<?php
namespace selling;

abstract class InvoiceElement extends \Element {

	use \FilterElement;

	private static ?InvoiceModel $model = NULL;

	const INCLUDING = 'including';
	const EXCLUDING = 'excluding';

	const GOOD = 'good';
	const SERVICE = 'service';
	const MIXED = 'mixed';

	const PAID = 'paid';
	const PARTIAL_PAID = 'partial-paid';
	const NOT_PAID = 'not-paid';
	const NEVER_PAID = 'never-paid';
	const FAILED = 'failed';

	const DRAFT = 'draft';
	const CONFIRMED = 'confirmed';
	const GENERATED = 'generated';
	const DELIVERED = 'delivered';
	const CANCELED = 'canceled';

	const WAITING = 'waiting';
	const NOW = 'now';
	const PROCESSING = 'processing';
	const FAIL = 'fail';
	const SUCCESS = 'success';

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

	protected string $module = 'selling\Invoice';
	protected string $package = 'selling';
	protected string $table = 'sellingInvoice';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'document' => ['int32', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'number' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'customer' => ['element32', 'selling\Customer', 'cast' => 'element'],
			'sales' => ['json', 'cast' => 'array'],
			'taxes' => ['enum', [\selling\Invoice::INCLUDING, \selling\Invoice::EXCLUDING], 'cast' => 'enum'],
			'nature' => ['enum', [\selling\Invoice::GOOD, \selling\Invoice::SERVICE, \selling\Invoice::MIXED], 'null' => TRUE, 'cast' => 'enum'],
			'organic' => ['bool', 'cast' => 'bool'],
			'conversion' => ['bool', 'cast' => 'bool'],
			'comment' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'content' => ['element32', 'selling\PdfContent', 'null' => TRUE, 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'hasVat' => ['bool', 'cast' => 'bool'],
			'vatByRate' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'vat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'cast' => 'float'],
			'priceExcludingVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'cast' => 'float'],
			'priceIncludingVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'cast' => 'float'],
			'date' => ['date', 'cast' => 'string'],
			'dueDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'paymentStatus' => ['enum', [\selling\Invoice::PAID, \selling\Invoice::PARTIAL_PAID, \selling\Invoice::NOT_PAID, \selling\Invoice::NEVER_PAID, \selling\Invoice::FAILED], 'null' => TRUE, 'cast' => 'enum'],
			'paymentAmount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'paymentCondition' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'header' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'footer' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\selling\Invoice::DRAFT, \selling\Invoice::CONFIRMED, \selling\Invoice::GENERATED, \selling\Invoice::DELIVERED, \selling\Invoice::CANCELED], 'cast' => 'enum'],
			'generation' => ['enum', [\selling\Invoice::WAITING, \selling\Invoice::NOW, \selling\Invoice::PROCESSING, \selling\Invoice::FAIL, \selling\Invoice::SUCCESS], 'null' => TRUE, 'cast' => 'enum'],
			'generationAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'closed' => ['bool', 'cast' => 'bool'],
			'closedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'closedBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'emailedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'paidAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'remindedAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'document', 'number', 'customer', 'sales', 'taxes', 'nature', 'organic', 'conversion', 'comment', 'content', 'farm', 'hasVat', 'vatByRate', 'vat', 'priceExcludingVat', 'priceIncludingVat', 'date', 'dueDate', 'paymentStatus', 'paymentAmount', 'paymentCondition', 'header', 'footer', 'status', 'generation', 'generationAt', 'closed', 'closedAt', 'closedBy', 'emailedAt', 'paidAt', 'remindedAt', 'createdAt'
		]);

		$this->propertiesToModule += [
			'customer' => 'selling\Customer',
			'content' => 'selling\PdfContent',
			'farm' => 'farm\Farm',
			'closedBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'customer']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'number']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'closed' :
				return FALSE;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'sales' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

			case 'taxes' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'nature' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'vatByRate' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

			case 'paymentStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'generation' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'sales' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'vatByRate' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

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

	public function whereDocument(...$data): InvoiceModel {
		return $this->where('document', ...$data);
	}

	public function whereNumber(...$data): InvoiceModel {
		return $this->where('number', ...$data);
	}

	public function whereCustomer(...$data): InvoiceModel {
		return $this->where('customer', ...$data);
	}

	public function whereSales(...$data): InvoiceModel {
		return $this->where('sales', ...$data);
	}

	public function whereTaxes(...$data): InvoiceModel {
		return $this->where('taxes', ...$data);
	}

	public function whereNature(...$data): InvoiceModel {
		return $this->where('nature', ...$data);
	}

	public function whereOrganic(...$data): InvoiceModel {
		return $this->where('organic', ...$data);
	}

	public function whereConversion(...$data): InvoiceModel {
		return $this->where('conversion', ...$data);
	}

	public function whereComment(...$data): InvoiceModel {
		return $this->where('comment', ...$data);
	}

	public function whereContent(...$data): InvoiceModel {
		return $this->where('content', ...$data);
	}

	public function whereFarm(...$data): InvoiceModel {
		return $this->where('farm', ...$data);
	}

	public function whereHasVat(...$data): InvoiceModel {
		return $this->where('hasVat', ...$data);
	}

	public function whereVatByRate(...$data): InvoiceModel {
		return $this->where('vatByRate', ...$data);
	}

	public function whereVat(...$data): InvoiceModel {
		return $this->where('vat', ...$data);
	}

	public function wherePriceExcludingVat(...$data): InvoiceModel {
		return $this->where('priceExcludingVat', ...$data);
	}

	public function wherePriceIncludingVat(...$data): InvoiceModel {
		return $this->where('priceIncludingVat', ...$data);
	}

	public function whereDate(...$data): InvoiceModel {
		return $this->where('date', ...$data);
	}

	public function whereDueDate(...$data): InvoiceModel {
		return $this->where('dueDate', ...$data);
	}

	public function wherePaymentStatus(...$data): InvoiceModel {
		return $this->where('paymentStatus', ...$data);
	}

	public function wherePaymentAmount(...$data): InvoiceModel {
		return $this->where('paymentAmount', ...$data);
	}

	public function wherePaymentCondition(...$data): InvoiceModel {
		return $this->where('paymentCondition', ...$data);
	}

	public function whereHeader(...$data): InvoiceModel {
		return $this->where('header', ...$data);
	}

	public function whereFooter(...$data): InvoiceModel {
		return $this->where('footer', ...$data);
	}

	public function whereStatus(...$data): InvoiceModel {
		return $this->where('status', ...$data);
	}

	public function whereGeneration(...$data): InvoiceModel {
		return $this->where('generation', ...$data);
	}

	public function whereGenerationAt(...$data): InvoiceModel {
		return $this->where('generationAt', ...$data);
	}

	public function whereClosed(...$data): InvoiceModel {
		return $this->where('closed', ...$data);
	}

	public function whereClosedAt(...$data): InvoiceModel {
		return $this->where('closedAt', ...$data);
	}

	public function whereClosedBy(...$data): InvoiceModel {
		return $this->where('closedBy', ...$data);
	}

	public function whereEmailedAt(...$data): InvoiceModel {
		return $this->where('emailedAt', ...$data);
	}

	public function wherePaidAt(...$data): InvoiceModel {
		return $this->where('paidAt', ...$data);
	}

	public function whereRemindedAt(...$data): InvoiceModel {
		return $this->where('remindedAt', ...$data);
	}

	public function whereCreatedAt(...$data): InvoiceModel {
		return $this->where('createdAt', ...$data);
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

	protected string $module = 'selling\Invoice';

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