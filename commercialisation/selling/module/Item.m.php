<?php
namespace selling;

abstract class ItemElement extends \Element {

	use \FilterElement;

	private static ?ItemModel $model = NULL;

	const PRIVATE = 'private';
	const PRO = 'pro';

	const SALE = 'sale';
	const SALE_MARKET = 'sale-market';
	const MARKET = 'market';
	const COMPOSITION = 'composition';

	const GOOD = 'good';
	const SERVICE = 'service';

	const NO = 'no';
	const ORGANIC = 'organic';
	const NATURE_PROGRES = 'nature-progres';
	const CONVERSION = 'conversion';

	const UNIT_PRICE = 'unit-price';
	const NUMBER = 'number';
	const PRICE = 'price';

	public static function getSelection(): array {
		return Item::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): ItemModel {
		if(self::$model === NULL) {
			self::$model = new ItemModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Item::'.$failName, $arguments, $wrapper);
	}

}


class ItemModel extends \ModuleModel {

	protected string $module = 'selling\Item';
	protected string $package = 'selling';
	protected string $table = 'sellingItem';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'reference' => ['text8', 'min' => 1, 'max' => NULL, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'sale' => ['element32', 'selling\Sale', 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'type' => ['enum', [\selling\Item::PRIVATE, \selling\Item::PRO], 'cast' => 'enum'],
			'profile' => ['enum', [\selling\Item::SALE, \selling\Item::SALE_MARKET, \selling\Item::MARKET, \selling\Item::COMPOSITION], 'cast' => 'enum'],
			'additional' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'origin' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'shop' => ['element32', 'shop\Shop', 'null' => TRUE, 'cast' => 'element'],
			'shopDate' => ['element32', 'shop\Date', 'null' => TRUE, 'cast' => 'element'],
			'shopProduct' => ['element32', 'shop\Product', 'null' => TRUE, 'cast' => 'element'],
			'product' => ['element32', 'selling\Product', 'null' => TRUE, 'cast' => 'element'],
			'composition' => ['element32', 'selling\Sale', 'null' => TRUE, 'cast' => 'element'],
			'ingredientOf' => ['element32', 'selling\Item', 'null' => TRUE, 'cast' => 'element'],
			'nature' => ['enum', [\selling\Item::GOOD, \selling\Item::SERVICE], 'cast' => 'enum'],
			'quality' => ['enum', [\selling\Item::NO, \selling\Item::ORGANIC, \selling\Item::NATURE_PROGRES, \selling\Item::CONVERSION], 'cast' => 'enum'],
			'parent' => ['element32', 'selling\Item', 'null' => TRUE, 'cast' => 'element'],
			'packaging' => ['decimal', 'digits' => 6, 'decimal' => 2, 'min' => 0.01, 'max' => 9999.99, 'null' => TRUE, 'cast' => 'float'],
			'unit' => ['element32', 'selling\Unit', 'null' => TRUE, 'cast' => 'element'],
			'unitPrice' => ['decimal', 'digits' => 10, 'decimal' => 4, 'min' => -999999.9999, 'max' => 999999.9999, 'cast' => 'float'],
			'unitPriceInitial' => ['decimal', 'digits' => 10, 'decimal' => 4, 'min' => -999999.9999, 'max' => 999999.9999, 'null' => TRUE, 'cast' => 'float'],
			'discount' => ['int8', 'min' => 0, 'max' => 100, 'cast' => 'int'],
			'number' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'price' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'priceStats' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'locked' => ['enum', [\selling\Item::UNIT_PRICE, \selling\Item::NUMBER, \selling\Item::PRICE], 'cast' => 'enum'],
			'vatRate' => ['decimal', 'digits' => 4, 'decimal' => 2, 'min' => 0.0, 'max' => 99.99, 'null' => TRUE, 'cast' => 'float'],
			'stats' => ['bool', 'cast' => 'bool'],
			'prepared' => ['bool', 'cast' => 'bool'],
			'account' => ['element32', 'account\Account', 'null' => TRUE, 'cast' => 'element'],
			'status' => ['enum', Sale::model()->getPropertyEnum('preparationStatus'), 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'deliveredAt' => ['date', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'reference', 'sale', 'customer', 'type', 'profile', 'additional', 'origin', 'farm', 'shop', 'shopDate', 'shopProduct', 'product', 'composition', 'ingredientOf', 'nature', 'quality', 'parent', 'packaging', 'unit', 'unitPrice', 'unitPriceInitial', 'discount', 'number', 'price', 'priceStats', 'locked', 'vatRate', 'stats', 'prepared', 'account', 'status', 'createdAt', 'deliveredAt'
		]);

		$this->propertiesToModule += [
			'sale' => 'selling\Sale',
			'customer' => 'selling\Customer',
			'farm' => 'farm\Farm',
			'shop' => 'shop\Shop',
			'shopDate' => 'shop\Date',
			'shopProduct' => 'shop\Product',
			'product' => 'selling\Product',
			'composition' => 'selling\Sale',
			'ingredientOf' => 'selling\Item',
			'parent' => 'selling\Item',
			'unit' => 'selling\Unit',
			'account' => 'account\Account',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'customer'],
			['farm', 'deliveredAt'],
			['product'],
			['sale'],
			['ingredientOf'],
			['parent']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'profile' :
				return Item::SALE;

			case 'nature' :
				return Item::GOOD;

			case 'discount' :
				return 0;

			case 'locked' :
				return Item::PRICE;

			case 'stats' :
				return TRUE;

			case 'prepared' :
				return FALSE;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'profile' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'nature' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'quality' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'locked' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): ItemModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ItemModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ItemModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): ItemModel {
		return $this->where('name', ...$data);
	}

	public function whereReference(...$data): ItemModel {
		return $this->where('reference', ...$data);
	}

	public function whereSale(...$data): ItemModel {
		return $this->where('sale', ...$data);
	}

	public function whereCustomer(...$data): ItemModel {
		return $this->where('customer', ...$data);
	}

	public function whereType(...$data): ItemModel {
		return $this->where('type', ...$data);
	}

	public function whereProfile(...$data): ItemModel {
		return $this->where('profile', ...$data);
	}

	public function whereAdditional(...$data): ItemModel {
		return $this->where('additional', ...$data);
	}

	public function whereOrigin(...$data): ItemModel {
		return $this->where('origin', ...$data);
	}

	public function whereFarm(...$data): ItemModel {
		return $this->where('farm', ...$data);
	}

	public function whereShop(...$data): ItemModel {
		return $this->where('shop', ...$data);
	}

	public function whereShopDate(...$data): ItemModel {
		return $this->where('shopDate', ...$data);
	}

	public function whereShopProduct(...$data): ItemModel {
		return $this->where('shopProduct', ...$data);
	}

	public function whereProduct(...$data): ItemModel {
		return $this->where('product', ...$data);
	}

	public function whereComposition(...$data): ItemModel {
		return $this->where('composition', ...$data);
	}

	public function whereIngredientOf(...$data): ItemModel {
		return $this->where('ingredientOf', ...$data);
	}

	public function whereNature(...$data): ItemModel {
		return $this->where('nature', ...$data);
	}

	public function whereQuality(...$data): ItemModel {
		return $this->where('quality', ...$data);
	}

	public function whereParent(...$data): ItemModel {
		return $this->where('parent', ...$data);
	}

	public function wherePackaging(...$data): ItemModel {
		return $this->where('packaging', ...$data);
	}

	public function whereUnit(...$data): ItemModel {
		return $this->where('unit', ...$data);
	}

	public function whereUnitPrice(...$data): ItemModel {
		return $this->where('unitPrice', ...$data);
	}

	public function whereUnitPriceInitial(...$data): ItemModel {
		return $this->where('unitPriceInitial', ...$data);
	}

	public function whereDiscount(...$data): ItemModel {
		return $this->where('discount', ...$data);
	}

	public function whereNumber(...$data): ItemModel {
		return $this->where('number', ...$data);
	}

	public function wherePrice(...$data): ItemModel {
		return $this->where('price', ...$data);
	}

	public function wherePriceStats(...$data): ItemModel {
		return $this->where('priceStats', ...$data);
	}

	public function whereLocked(...$data): ItemModel {
		return $this->where('locked', ...$data);
	}

	public function whereVatRate(...$data): ItemModel {
		return $this->where('vatRate', ...$data);
	}

	public function whereStats(...$data): ItemModel {
		return $this->where('stats', ...$data);
	}

	public function wherePrepared(...$data): ItemModel {
		return $this->where('prepared', ...$data);
	}

	public function whereAccount(...$data): ItemModel {
		return $this->where('account', ...$data);
	}

	public function whereStatus(...$data): ItemModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): ItemModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereDeliveredAt(...$data): ItemModel {
		return $this->where('deliveredAt', ...$data);
	}


}


abstract class ItemCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Item {

		$e = new Item();

		if(empty($id)) {
			Item::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Item::getSelection();
		}

		if(Item::model()
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
			$properties = Item::getSelection();
		}

		if($sort !== NULL) {
			Item::model()->sort($sort);
		}

		return Item::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Item {

		return new Item(['id' => NULL]);

	}

	public static function create(Item $e): void {

		Item::model()->insert($e);

	}

	public static function update(Item $e, array $properties): void {

		$e->expects(['id']);

		Item::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Item $e, array $properties): void {

		Item::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Item $e): void {

		$e->expects(['id']);

		Item::model()->delete($e);

	}

}


class ItemPage extends \ModulePage {

	protected string $module = 'selling\Item';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ItemLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ItemLib::getPropertiesUpdate()
		);
	}

}
?>