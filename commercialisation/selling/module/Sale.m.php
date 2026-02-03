<?php
namespace selling;

abstract class SaleElement extends \Element {

	use \FilterElement;

	private static ?SaleModel $model = NULL;

	const SALE = 'sale';
	const SALE_MARKET = 'sale-market';
	const MARKET = 'market';
	const COMPOSITION = 'composition';

	const INCLUDING = 'including';
	const EXCLUDING = 'excluding';

	const GOOD = 'good';
	const SERVICE = 'service';
	const MIXED = 'mixed';

	const PRIVATE = 'private';
	const PRO = 'pro';

	const DRAFT = 'draft';
	const BASKET = 'basket';
	const CONFIRMED = 'confirmed';
	const SELLING = 'selling';
	const PREPARED = 'prepared';
	const DELIVERED = 'delivered';
	const CANCELED = 'canceled';
	const EXPIRED = 'expired';

	const NOT_PAID = 'not-paid';
	const PAID = 'paid';
	const NEVER_PAID = 'never-paid';

	const INITIALIZED = 'initialized';
	const SUCCESS = 'success';
	const FAILURE = 'failure';

	public static function getSelection(): array {
		return Sale::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): SaleModel {
		if(self::$model === NULL) {
			self::$model = new SaleModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Sale::'.$failName, $arguments, $wrapper);
	}

}


class SaleModel extends \ModuleModel {

	protected string $module = 'selling\Sale';
	protected string $package = 'selling';
	protected string $table = 'sellingSale';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'document' => ['int32', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'profile' => ['enum', [\selling\Sale::SALE, \selling\Sale::SALE_MARKET, \selling\Sale::MARKET, \selling\Sale::COMPOSITION], 'cast' => 'enum'],
			'taxes' => ['enum', [\selling\Sale::INCLUDING, \selling\Sale::EXCLUDING], 'cast' => 'enum'],
			'organic' => ['bool', 'cast' => 'bool'],
			'conversion' => ['bool', 'cast' => 'bool'],
			'nature' => ['enum', [\selling\Sale::GOOD, \selling\Sale::SERVICE, \selling\Sale::MIXED], 'null' => TRUE, 'cast' => 'enum'],
			'type' => ['enum', [\selling\Sale::PRIVATE, \selling\Sale::PRO], 'cast' => 'enum'],
			'discount' => ['int8', 'min' => 0, 'max' => 100, 'cast' => 'int'],
			'items' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'hasVat' => ['bool', 'cast' => 'bool'],
			'vat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'vatByRate' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'priceGross' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'priceExcludingVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'priceIncludingVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'shippingVatRate' => ['decimal', 'digits' => 4, 'decimal' => 2, 'min' => -99.99, 'max' => 99.99, 'null' => TRUE, 'cast' => 'float'],
			'shippingVatFixed' => ['bool', 'cast' => 'bool'],
			'shipping' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.01, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'shippingExcludingVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'secured' => ['bool', 'cast' => 'bool'],
			'securedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'closed' => ['bool', 'cast' => 'bool'],
			'closedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'closedBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'preparationStatus' => ['enum', [\selling\Sale::COMPOSITION, \selling\Sale::DRAFT, \selling\Sale::BASKET, \selling\Sale::CONFIRMED, \selling\Sale::SELLING, \selling\Sale::PREPARED, \selling\Sale::DELIVERED, \selling\Sale::CANCELED, \selling\Sale::EXPIRED], 'cast' => 'enum'],
			'paymentStatus' => ['enum', [\selling\Sale::NOT_PAID, \selling\Sale::PAID, \selling\Sale::NEVER_PAID], 'null' => TRUE, 'cast' => 'enum'],
			'onlinePaymentStatus' => ['enum', [\selling\Sale::INITIALIZED, \selling\Sale::SUCCESS, \selling\Sale::FAILURE], 'null' => TRUE, 'cast' => 'enum'],
			'compositionOf' => ['element32', 'selling\Product', 'null' => TRUE, 'cast' => 'element'],
			'compositionEndAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'marketSales' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'marketParent' => ['element32', 'selling\Sale', 'null' => TRUE, 'cast' => 'element'],
			'orderFormValidUntil' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'orderFormPaymentCondition' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'orderFormHeader' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'orderFormFooter' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'deliveryNoteDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'deliveryNoteHeader' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'deliveryNoteFooter' => ['editor16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'invoice' => ['element32', 'selling\Invoice', 'null' => TRUE, 'cast' => 'element'],
			'shop' => ['element32', 'shop\Shop', 'null' => TRUE, 'cast' => 'element'],
			'shopDate' => ['element32', 'shop\Date', 'null' => TRUE, 'cast' => 'element'],
			'shopLocked' => ['bool', 'cast' => 'bool'],
			'shopShared' => ['bool', 'cast' => 'bool'],
			'shopUpdated' => ['bool', 'cast' => 'bool'],
			'shopPoint' => ['element32', 'shop\Point', 'null' => TRUE, 'cast' => 'element'],
			'shopComment' => ['text8', 'min' => 1, 'max' => 150, 'null' => TRUE, 'cast' => 'string'],
			'deliveryStreet1' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryStreet2' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryPostcode' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryCity' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'deliveryCountry' => ['element32', 'user\Country', 'null' => TRUE, 'cast' => 'element'],
			'comment' => ['text24', 'null' => TRUE, 'cast' => 'string'],
			'stats' => ['bool', 'cast' => 'bool'],
			'crc32' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'deliveredAt' => ['date', 'cast' => 'string'],
			'paidAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'expiresAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'statusAt' => ['datetime', 'cast' => 'string'],
			'statusBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'accountingHash' => ['textFixed', 'min' => 20, 'max' => 20, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'document', 'farm', 'customer', 'profile', 'taxes', 'organic', 'conversion', 'nature', 'type', 'discount', 'items', 'hasVat', 'vat', 'vatByRate', 'priceGross', 'priceExcludingVat', 'priceIncludingVat', 'shippingVatRate', 'shippingVatFixed', 'shipping', 'shippingExcludingVat', 'secured', 'securedAt', 'closed', 'closedAt', 'closedBy', 'preparationStatus', 'paymentStatus', 'onlinePaymentStatus', 'compositionOf', 'compositionEndAt', 'marketSales', 'marketParent', 'orderFormValidUntil', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter', 'deliveryNoteDate', 'deliveryNoteHeader', 'deliveryNoteFooter', 'invoice', 'shop', 'shopDate', 'shopLocked', 'shopShared', 'shopUpdated', 'shopPoint', 'shopComment', 'deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'deliveryCountry', 'comment', 'stats', 'crc32', 'createdAt', 'createdBy', 'deliveredAt', 'paidAt', 'expiresAt', 'statusAt', 'statusBy', 'accountingHash'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'customer' => 'selling\Customer',
			'closedBy' => 'user\User',
			'compositionOf' => 'selling\Product',
			'marketParent' => 'selling\Sale',
			'invoice' => 'selling\Invoice',
			'shop' => 'shop\Shop',
			'shopDate' => 'shop\Date',
			'shopPoint' => 'shop\Point',
			'deliveryCountry' => 'user\Country',
			'createdBy' => 'user\User',
			'statusBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['customer'],
			['shopDate'],
			['shop']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['compositionOf', 'deliveredAt'],
			['farm', 'document']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'profile' :
				return Sale::SALE;

			case 'organic' :
				return FALSE;

			case 'conversion' :
				return FALSE;

			case 'discount' :
				return 0;

			case 'items' :
				return 0;

			case 'shippingVatFixed' :
				return FALSE;

			case 'secured' :
				return FALSE;

			case 'closed' :
				return FALSE;

			case 'preparationStatus' :
				return Sale::DRAFT;

			case 'shopLocked' :
				return FALSE;

			case 'shopShared' :
				return FALSE;

			case 'shopUpdated' :
				return FALSE;

			case 'stats' :
				return TRUE;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			case 'statusAt' :
				return new \Sql('NOW()');

			case 'statusBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'profile' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'taxes' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'nature' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'vatByRate' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

			case 'preparationStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'paymentStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'onlinePaymentStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'vatByRate' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): SaleModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SaleModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SaleModel {
		return $this->where('id', ...$data);
	}

	public function whereDocument(...$data): SaleModel {
		return $this->where('document', ...$data);
	}

	public function whereFarm(...$data): SaleModel {
		return $this->where('farm', ...$data);
	}

	public function whereCustomer(...$data): SaleModel {
		return $this->where('customer', ...$data);
	}

	public function whereProfile(...$data): SaleModel {
		return $this->where('profile', ...$data);
	}

	public function whereTaxes(...$data): SaleModel {
		return $this->where('taxes', ...$data);
	}

	public function whereOrganic(...$data): SaleModel {
		return $this->where('organic', ...$data);
	}

	public function whereConversion(...$data): SaleModel {
		return $this->where('conversion', ...$data);
	}

	public function whereNature(...$data): SaleModel {
		return $this->where('nature', ...$data);
	}

	public function whereType(...$data): SaleModel {
		return $this->where('type', ...$data);
	}

	public function whereDiscount(...$data): SaleModel {
		return $this->where('discount', ...$data);
	}

	public function whereItems(...$data): SaleModel {
		return $this->where('items', ...$data);
	}

	public function whereHasVat(...$data): SaleModel {
		return $this->where('hasVat', ...$data);
	}

	public function whereVat(...$data): SaleModel {
		return $this->where('vat', ...$data);
	}

	public function whereVatByRate(...$data): SaleModel {
		return $this->where('vatByRate', ...$data);
	}

	public function wherePriceGross(...$data): SaleModel {
		return $this->where('priceGross', ...$data);
	}

	public function wherePriceExcludingVat(...$data): SaleModel {
		return $this->where('priceExcludingVat', ...$data);
	}

	public function wherePriceIncludingVat(...$data): SaleModel {
		return $this->where('priceIncludingVat', ...$data);
	}

	public function whereShippingVatRate(...$data): SaleModel {
		return $this->where('shippingVatRate', ...$data);
	}

	public function whereShippingVatFixed(...$data): SaleModel {
		return $this->where('shippingVatFixed', ...$data);
	}

	public function whereShipping(...$data): SaleModel {
		return $this->where('shipping', ...$data);
	}

	public function whereShippingExcludingVat(...$data): SaleModel {
		return $this->where('shippingExcludingVat', ...$data);
	}

	public function whereSecured(...$data): SaleModel {
		return $this->where('secured', ...$data);
	}

	public function whereSecuredAt(...$data): SaleModel {
		return $this->where('securedAt', ...$data);
	}

	public function whereClosed(...$data): SaleModel {
		return $this->where('closed', ...$data);
	}

	public function whereClosedAt(...$data): SaleModel {
		return $this->where('closedAt', ...$data);
	}

	public function whereClosedBy(...$data): SaleModel {
		return $this->where('closedBy', ...$data);
	}

	public function wherePreparationStatus(...$data): SaleModel {
		return $this->where('preparationStatus', ...$data);
	}

	public function wherePaymentStatus(...$data): SaleModel {
		return $this->where('paymentStatus', ...$data);
	}

	public function whereOnlinePaymentStatus(...$data): SaleModel {
		return $this->where('onlinePaymentStatus', ...$data);
	}

	public function whereCompositionOf(...$data): SaleModel {
		return $this->where('compositionOf', ...$data);
	}

	public function whereCompositionEndAt(...$data): SaleModel {
		return $this->where('compositionEndAt', ...$data);
	}

	public function whereMarketSales(...$data): SaleModel {
		return $this->where('marketSales', ...$data);
	}

	public function whereMarketParent(...$data): SaleModel {
		return $this->where('marketParent', ...$data);
	}

	public function whereOrderFormValidUntil(...$data): SaleModel {
		return $this->where('orderFormValidUntil', ...$data);
	}

	public function whereOrderFormPaymentCondition(...$data): SaleModel {
		return $this->where('orderFormPaymentCondition', ...$data);
	}

	public function whereOrderFormHeader(...$data): SaleModel {
		return $this->where('orderFormHeader', ...$data);
	}

	public function whereOrderFormFooter(...$data): SaleModel {
		return $this->where('orderFormFooter', ...$data);
	}

	public function whereDeliveryNoteDate(...$data): SaleModel {
		return $this->where('deliveryNoteDate', ...$data);
	}

	public function whereDeliveryNoteHeader(...$data): SaleModel {
		return $this->where('deliveryNoteHeader', ...$data);
	}

	public function whereDeliveryNoteFooter(...$data): SaleModel {
		return $this->where('deliveryNoteFooter', ...$data);
	}

	public function whereInvoice(...$data): SaleModel {
		return $this->where('invoice', ...$data);
	}

	public function whereShop(...$data): SaleModel {
		return $this->where('shop', ...$data);
	}

	public function whereShopDate(...$data): SaleModel {
		return $this->where('shopDate', ...$data);
	}

	public function whereShopLocked(...$data): SaleModel {
		return $this->where('shopLocked', ...$data);
	}

	public function whereShopShared(...$data): SaleModel {
		return $this->where('shopShared', ...$data);
	}

	public function whereShopUpdated(...$data): SaleModel {
		return $this->where('shopUpdated', ...$data);
	}

	public function whereShopPoint(...$data): SaleModel {
		return $this->where('shopPoint', ...$data);
	}

	public function whereShopComment(...$data): SaleModel {
		return $this->where('shopComment', ...$data);
	}

	public function whereDeliveryStreet1(...$data): SaleModel {
		return $this->where('deliveryStreet1', ...$data);
	}

	public function whereDeliveryStreet2(...$data): SaleModel {
		return $this->where('deliveryStreet2', ...$data);
	}

	public function whereDeliveryPostcode(...$data): SaleModel {
		return $this->where('deliveryPostcode', ...$data);
	}

	public function whereDeliveryCity(...$data): SaleModel {
		return $this->where('deliveryCity', ...$data);
	}

	public function whereDeliveryCountry(...$data): SaleModel {
		return $this->where('deliveryCountry', ...$data);
	}

	public function whereComment(...$data): SaleModel {
		return $this->where('comment', ...$data);
	}

	public function whereStats(...$data): SaleModel {
		return $this->where('stats', ...$data);
	}

	public function whereCrc32(...$data): SaleModel {
		return $this->where('crc32', ...$data);
	}

	public function whereCreatedAt(...$data): SaleModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): SaleModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereDeliveredAt(...$data): SaleModel {
		return $this->where('deliveredAt', ...$data);
	}

	public function wherePaidAt(...$data): SaleModel {
		return $this->where('paidAt', ...$data);
	}

	public function whereExpiresAt(...$data): SaleModel {
		return $this->where('expiresAt', ...$data);
	}

	public function whereStatusAt(...$data): SaleModel {
		return $this->where('statusAt', ...$data);
	}

	public function whereStatusBy(...$data): SaleModel {
		return $this->where('statusBy', ...$data);
	}

	public function whereAccountingHash(...$data): SaleModel {
		return $this->where('accountingHash', ...$data);
	}


}


abstract class SaleCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Sale {

		$e = new Sale();

		if(empty($id)) {
			Sale::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Sale::getSelection();
		}

		if(Sale::model()
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
			$properties = Sale::getSelection();
		}

		if($sort !== NULL) {
			Sale::model()->sort($sort);
		}

		return Sale::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Sale {

		return new Sale($properties);

	}

	public static function create(Sale $e): void {

		Sale::model()->insert($e);

	}

	public static function update(Sale $e, array $properties): void {

		$e->expects(['id']);

		Sale::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Sale $e, array $properties): void {

		Sale::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Sale $e): void {

		$e->expects(['id']);

		Sale::model()->delete($e);

	}

}


class SalePage extends \ModulePage {

	protected string $module = 'selling\Sale';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SaleLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SaleLib::getPropertiesUpdate()
		);
	}

}
?>