<?php
namespace farm;

abstract class ConfigurationElement extends \Element {

	use \FilterElement;

	private static ?ConfigurationModel $model = NULL;

	const ALL = 'all';
	const PRIVATE = 'private';
	const PRO = 'pro';
	const DISABLED = 'disabled';

	const NUMBER = 'number';
	const PRICE = 'price';

	public static function getSelection(): array {
		return Configuration::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): ConfigurationModel {
		if(self::$model === NULL) {
			self::$model = new ConfigurationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Configuration::'.$failName, $arguments, $wrapper);
	}

}


class ConfigurationModel extends \ModuleModel {

	protected string $module = 'farm\Configuration';
	protected string $package = 'farm';
	protected string $table = 'farmConfiguration';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'unique' => TRUE, 'cast' => 'element'],
			'documentSales' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'documentInvoices' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'taxCountry' => ['element32', 'user\Country', 'cast' => 'element'],
			'taxCountryVerified' => ['bool', 'cast' => 'bool'],
			'hasVat' => ['bool', 'cast' => 'bool'],
			'defaultVat' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'defaultVatShipping' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'vatNumber' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'organicCertifier' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'paymentMode' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'saleClosing' => ['int16', 'min' => 7, 'max' => 365, 'cast' => 'int'],
			'documentCopy' => ['bool', 'cast' => 'bool'],
			'documentTarget' => ['enum', [\farm\Configuration::ALL, \farm\Configuration::PRIVATE, \farm\Configuration::PRO, \farm\Configuration::DISABLED], 'cast' => 'enum'],
			'orderFormPrefix' => ['text8', 'min' => 1, 'max' => 15, 'cast' => 'string'],
			'orderFormDelivery' => ['bool', 'cast' => 'bool'],
			'orderFormPaymentCondition' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'orderFormHeader' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'orderFormFooter' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'deliveryNotePrefix' => ['text8', 'min' => 1, 'max' => 15, 'cast' => 'string'],
			'deliveryNoteHeader' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'deliveryNoteFooter' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'creditPrefix' => ['text8', 'min' => 1, 'max' => 15, 'cast' => 'string'],
			'invoiceDue' => ['bool', 'cast' => 'bool'],
			'invoiceDueDays' => ['int16', 'min' => 0, 'max' => 360, 'null' => TRUE, 'cast' => 'int'],
			'invoiceDueMonth' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'invoicePrefix' => ['text8', 'min' => 1, 'max' => 15, 'cast' => 'string'],
			'invoicePaymentCondition' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'invoiceHeader' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'invoiceFooter' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'marketSalePaymentMethod' => ['element32', 'payment\Method', 'null' => TRUE, 'cast' => 'element'],
			'marketSaleDefaultDecimal' => ['enum', [\farm\Configuration::NUMBER, \farm\Configuration::PRICE], 'cast' => 'enum'],
			'pdfNaturalOrder' => ['bool', 'cast' => 'bool'],
			'profileAccount' => ['json', 'null' => TRUE, 'cast' => 'array'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'documentSales', 'documentInvoices', 'taxCountry', 'taxCountryVerified', 'hasVat', 'defaultVat', 'defaultVatShipping', 'vatNumber', 'organicCertifier', 'paymentMode', 'saleClosing', 'documentCopy', 'documentTarget', 'orderFormPrefix', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter', 'deliveryNotePrefix', 'deliveryNoteHeader', 'deliveryNoteFooter', 'creditPrefix', 'invoiceDue', 'invoiceDueDays', 'invoiceDueMonth', 'invoicePrefix', 'invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter', 'marketSalePaymentMethod', 'marketSaleDefaultDecimal', 'pdfNaturalOrder', 'profileAccount'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'taxCountry' => 'user\Country',
			'marketSalePaymentMethod' => 'payment\Method',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'documentSales' :
				return 0;

			case 'documentInvoices' :
				return 0;

			case 'taxCountryVerified' :
				return FALSE;

			case 'hasVat' :
				return TRUE;

			case 'saleClosing' :
				return 30;

			case 'documentCopy' :
				return FALSE;

			case 'documentTarget' :
				return Configuration::PRO;

			case 'orderFormPrefix' :
				return \farm\ConfigurationUi::getDefaultOrderFormPrefix();

			case 'orderFormDelivery' :
				return TRUE;

			case 'deliveryNotePrefix' :
				return \farm\ConfigurationUi::getDefaultDeliveryNotePrefix();

			case 'creditPrefix' :
				return \farm\ConfigurationUi::getDefaultCreditPrefix();

			case 'invoiceDue' :
				return FALSE;

			case 'invoicePrefix' :
				return \farm\ConfigurationUi::getDefaultInvoicePrefix();

			case 'marketSaleDefaultDecimal' :
				return Configuration::PRICE;

			case 'pdfNaturalOrder' :
				return FALSE;

			case 'profileAccount' :
				return [];

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'documentTarget' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'marketSaleDefaultDecimal' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'profileAccount' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'profileAccount' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): ConfigurationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ConfigurationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ConfigurationModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): ConfigurationModel {
		return $this->where('farm', ...$data);
	}

	public function whereDocumentSales(...$data): ConfigurationModel {
		return $this->where('documentSales', ...$data);
	}

	public function whereDocumentInvoices(...$data): ConfigurationModel {
		return $this->where('documentInvoices', ...$data);
	}

	public function whereTaxCountry(...$data): ConfigurationModel {
		return $this->where('taxCountry', ...$data);
	}

	public function whereTaxCountryVerified(...$data): ConfigurationModel {
		return $this->where('taxCountryVerified', ...$data);
	}

	public function whereHasVat(...$data): ConfigurationModel {
		return $this->where('hasVat', ...$data);
	}

	public function whereDefaultVat(...$data): ConfigurationModel {
		return $this->where('defaultVat', ...$data);
	}

	public function whereDefaultVatShipping(...$data): ConfigurationModel {
		return $this->where('defaultVatShipping', ...$data);
	}

	public function whereVatNumber(...$data): ConfigurationModel {
		return $this->where('vatNumber', ...$data);
	}

	public function whereOrganicCertifier(...$data): ConfigurationModel {
		return $this->where('organicCertifier', ...$data);
	}

	public function wherePaymentMode(...$data): ConfigurationModel {
		return $this->where('paymentMode', ...$data);
	}

	public function whereSaleClosing(...$data): ConfigurationModel {
		return $this->where('saleClosing', ...$data);
	}

	public function whereDocumentCopy(...$data): ConfigurationModel {
		return $this->where('documentCopy', ...$data);
	}

	public function whereDocumentTarget(...$data): ConfigurationModel {
		return $this->where('documentTarget', ...$data);
	}

	public function whereOrderFormPrefix(...$data): ConfigurationModel {
		return $this->where('orderFormPrefix', ...$data);
	}

	public function whereOrderFormDelivery(...$data): ConfigurationModel {
		return $this->where('orderFormDelivery', ...$data);
	}

	public function whereOrderFormPaymentCondition(...$data): ConfigurationModel {
		return $this->where('orderFormPaymentCondition', ...$data);
	}

	public function whereOrderFormHeader(...$data): ConfigurationModel {
		return $this->where('orderFormHeader', ...$data);
	}

	public function whereOrderFormFooter(...$data): ConfigurationModel {
		return $this->where('orderFormFooter', ...$data);
	}

	public function whereDeliveryNotePrefix(...$data): ConfigurationModel {
		return $this->where('deliveryNotePrefix', ...$data);
	}

	public function whereDeliveryNoteHeader(...$data): ConfigurationModel {
		return $this->where('deliveryNoteHeader', ...$data);
	}

	public function whereDeliveryNoteFooter(...$data): ConfigurationModel {
		return $this->where('deliveryNoteFooter', ...$data);
	}

	public function whereCreditPrefix(...$data): ConfigurationModel {
		return $this->where('creditPrefix', ...$data);
	}

	public function whereInvoiceDue(...$data): ConfigurationModel {
		return $this->where('invoiceDue', ...$data);
	}

	public function whereInvoiceDueDays(...$data): ConfigurationModel {
		return $this->where('invoiceDueDays', ...$data);
	}

	public function whereInvoiceDueMonth(...$data): ConfigurationModel {
		return $this->where('invoiceDueMonth', ...$data);
	}

	public function whereInvoicePrefix(...$data): ConfigurationModel {
		return $this->where('invoicePrefix', ...$data);
	}

	public function whereInvoicePaymentCondition(...$data): ConfigurationModel {
		return $this->where('invoicePaymentCondition', ...$data);
	}

	public function whereInvoiceHeader(...$data): ConfigurationModel {
		return $this->where('invoiceHeader', ...$data);
	}

	public function whereInvoiceFooter(...$data): ConfigurationModel {
		return $this->where('invoiceFooter', ...$data);
	}

	public function whereMarketSalePaymentMethod(...$data): ConfigurationModel {
		return $this->where('marketSalePaymentMethod', ...$data);
	}

	public function whereMarketSaleDefaultDecimal(...$data): ConfigurationModel {
		return $this->where('marketSaleDefaultDecimal', ...$data);
	}

	public function wherePdfNaturalOrder(...$data): ConfigurationModel {
		return $this->where('pdfNaturalOrder', ...$data);
	}

	public function whereProfileAccount(...$data): ConfigurationModel {
		return $this->where('profileAccount', ...$data);
	}


}


abstract class ConfigurationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Configuration {

		$e = new Configuration();

		if(empty($id)) {
			Configuration::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Configuration::getSelection();
		}

		if(Configuration::model()
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
			$properties = Configuration::getSelection();
		}

		if($sort !== NULL) {
			Configuration::model()->sort($sort);
		}

		return Configuration::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Configuration {

		return new Configuration(['id' => NULL]);

	}

	public static function create(Configuration $e): void {

		Configuration::model()->insert($e);

	}

	public static function update(Configuration $e, array $properties): void {

		$e->expects(['id']);

		Configuration::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Configuration $e, array $properties): void {

		Configuration::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Configuration $e): void {

		$e->expects(['id']);

		Configuration::model()->delete($e);

	}

}


class ConfigurationPage extends \ModulePage {

	protected string $module = 'farm\Configuration';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ConfigurationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ConfigurationLib::getPropertiesUpdate()
		);
	}

}
?>