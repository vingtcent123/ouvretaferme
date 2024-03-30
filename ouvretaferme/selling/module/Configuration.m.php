<?php
namespace selling;

abstract class ConfigurationElement extends \Element {

	use \FilterElement;

	private static ?ConfigurationModel $model = NULL;

	public static function getSelection(): array {
		return Configuration::model()->getProperties();
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

	protected string $module = 'selling\Configuration';
	protected string $package = 'selling';
	protected string $table = 'sellingConfiguration';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'unique' => TRUE, 'cast' => 'element'],
			'documentSales' => ['int32', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'documentInvoices' => ['int32', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'hasVat' => ['bool', 'cast' => 'bool'],
			'defaultVat' => ['int8', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'defaultVatShipping' => ['int8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'legalEmail' => ['email', 'null' => TRUE, 'cast' => 'string'],
			'legalName' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceStreet1' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceStreet2' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoicePostcode' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceCity' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceRegistration' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'invoiceVat' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'organicCertifier' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'paymentMode' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'documentCopy' => ['bool', 'cast' => 'bool'],
			'orderFormDelivery' => ['bool', 'cast' => 'bool'],
			'orderFormPaymentCondition' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'orderFormHeader' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'orderFormFooter' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'invoicePaymentCondition' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'invoiceHeader' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
			'invoiceFooter' => ['editor16', 'min' => 1, 'max' => 400, 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'documentSales', 'documentInvoices', 'hasVat', 'defaultVat', 'defaultVatShipping', 'legalEmail', 'legalName', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceRegistration', 'invoiceVat', 'organicCertifier', 'paymentMode', 'documentCopy', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter', 'invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
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

			case 'hasVat' :
				return TRUE;

			case 'documentCopy' :
				return FALSE;

			case 'orderFormDelivery' :
				return TRUE;

			default :
				return parent::getDefaultValue($property);

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

	public function whereHasVat(...$data): ConfigurationModel {
		return $this->where('hasVat', ...$data);
	}

	public function whereDefaultVat(...$data): ConfigurationModel {
		return $this->where('defaultVat', ...$data);
	}

	public function whereDefaultVatShipping(...$data): ConfigurationModel {
		return $this->where('defaultVatShipping', ...$data);
	}

	public function whereLegalEmail(...$data): ConfigurationModel {
		return $this->where('legalEmail', ...$data);
	}

	public function whereLegalName(...$data): ConfigurationModel {
		return $this->where('legalName', ...$data);
	}

	public function whereInvoiceStreet1(...$data): ConfigurationModel {
		return $this->where('invoiceStreet1', ...$data);
	}

	public function whereInvoiceStreet2(...$data): ConfigurationModel {
		return $this->where('invoiceStreet2', ...$data);
	}

	public function whereInvoicePostcode(...$data): ConfigurationModel {
		return $this->where('invoicePostcode', ...$data);
	}

	public function whereInvoiceCity(...$data): ConfigurationModel {
		return $this->where('invoiceCity', ...$data);
	}

	public function whereInvoiceRegistration(...$data): ConfigurationModel {
		return $this->where('invoiceRegistration', ...$data);
	}

	public function whereInvoiceVat(...$data): ConfigurationModel {
		return $this->where('invoiceVat', ...$data);
	}

	public function whereOrganicCertifier(...$data): ConfigurationModel {
		return $this->where('organicCertifier', ...$data);
	}

	public function wherePaymentMode(...$data): ConfigurationModel {
		return $this->where('paymentMode', ...$data);
	}

	public function whereDocumentCopy(...$data): ConfigurationModel {
		return $this->where('documentCopy', ...$data);
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

	public function whereInvoicePaymentCondition(...$data): ConfigurationModel {
		return $this->where('invoicePaymentCondition', ...$data);
	}

	public function whereInvoiceHeader(...$data): ConfigurationModel {
		return $this->where('invoiceHeader', ...$data);
	}

	public function whereInvoiceFooter(...$data): ConfigurationModel {
		return $this->where('invoiceFooter', ...$data);
	}


}


abstract class ConfigurationCrud extends \ModuleCrud {

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

	protected string $module = 'selling\Configuration';

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