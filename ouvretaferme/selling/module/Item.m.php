<?php
namespace selling;

abstract class ItemElement extends \Element {

	use \FilterElement;

	private static ?ItemModel $model = NULL;

	const PRIVATE = 'private';
	const PRO = 'pro';

	const ORGANIC = 'organic';
	const NATURE_PROGRES = 'nature-progres';
	const CONVERSION = 'conversion';

	const KG = 'kg';
	const GRAM = 'gram';
	const GRAM_100 = 'gram-100';
	const GRAM_250 = 'gram-250';
	const GRAM_500 = 'gram-500';
	const UNIT = 'unit';
	const BUNCH = 'bunch';
	const PLANT = 'plant';

	const UNIT_PRICE = 'unit-price';
	const NUMBER = 'number';
	const PRICE = 'price';

	public static function getSelection(): array {
		return Item::model()->getProperties();
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
			'sale' => ['element32', 'selling\Sale', 'cast' => 'element'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'type' => ['enum', [\selling\Item::PRIVATE, \selling\Item::PRO], 'cast' => 'enum'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'shop' => ['element32', 'shop\Shop', 'null' => TRUE, 'cast' => 'element'],
			'shopDate' => ['element32', 'shop\Date', 'null' => TRUE, 'cast' => 'element'],
			'product' => ['element32', 'selling\Product', 'null' => TRUE, 'cast' => 'element'],
			'quality' => ['enum', [\selling\Item::ORGANIC, \selling\Item::NATURE_PROGRES, \selling\Item::CONVERSION], 'null' => TRUE, 'cast' => 'enum'],
			'parent' => ['element32', 'selling\Item', 'null' => TRUE, 'cast' => 'element'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'packaging' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'unit' => ['enum', [\selling\Item::KG, \selling\Item::GRAM, \selling\Item::GRAM_100, \selling\Item::GRAM_250, \selling\Item::GRAM_500, \selling\Item::UNIT, \selling\Item::BUNCH, \selling\Item::PLANT], 'null' => TRUE, 'cast' => 'enum'],
			'unitPrice' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'discount' => ['int8', 'min' => 0, 'max' => 100, 'cast' => 'int'],
			'number' => ['float32', 'min' => 0.0, 'max' => NULL, 'cast' => 'float'],
			'price' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'priceExcludingVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'locked' => ['enum', [\selling\Item::UNIT_PRICE, \selling\Item::NUMBER, \selling\Item::PRICE], 'cast' => 'enum'],
			'vatRate' => ['decimal', 'digits' => 4, 'decimal' => 2, 'min' => 0.0, 'max' => 100, 'null' => TRUE, 'cast' => 'float'],
			'stats' => ['bool', 'cast' => 'bool'],
			'status' => ['enum', Sale::model()->getPropertyEnum('preparationStatus'), 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'deliveredAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'sale', 'customer', 'type', 'farm', 'shop', 'shopDate', 'product', 'quality', 'parent', 'description', 'packaging', 'unit', 'unitPrice', 'discount', 'number', 'price', 'priceExcludingVat', 'locked', 'vatRate', 'stats', 'status', 'createdAt', 'deliveredAt'
		]);

		$this->propertiesToModule += [
			'sale' => 'selling\Sale',
			'customer' => 'selling\Customer',
			'farm' => 'farm\Farm',
			'shop' => 'shop\Shop',
			'shopDate' => 'shop\Date',
			'product' => 'selling\Product',
			'parent' => 'selling\Item',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'customer'],
			['product'],
			['sale'],
			['parent']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'discount' :
				return 0;

			case 'locked' :
				return Item::PRICE;

			case 'stats' :
				return TRUE;

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

			case 'quality' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'unit' :
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

	public function whereSale(...$data): ItemModel {
		return $this->where('sale', ...$data);
	}

	public function whereCustomer(...$data): ItemModel {
		return $this->where('customer', ...$data);
	}

	public function whereType(...$data): ItemModel {
		return $this->where('type', ...$data);
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

	public function whereProduct(...$data): ItemModel {
		return $this->where('product', ...$data);
	}

	public function whereQuality(...$data): ItemModel {
		return $this->where('quality', ...$data);
	}

	public function whereParent(...$data): ItemModel {
		return $this->where('parent', ...$data);
	}

	public function whereDescription(...$data): ItemModel {
		return $this->where('description', ...$data);
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

	public function whereDiscount(...$data): ItemModel {
		return $this->where('discount', ...$data);
	}

	public function whereNumber(...$data): ItemModel {
		return $this->where('number', ...$data);
	}

	public function wherePrice(...$data): ItemModel {
		return $this->where('price', ...$data);
	}

	public function wherePriceExcludingVat(...$data): ItemModel {
		return $this->where('priceExcludingVat', ...$data);
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