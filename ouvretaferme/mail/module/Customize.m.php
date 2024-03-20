<?php
namespace mail;

abstract class CustomizeElement extends \Element {

	use \FilterElement;

	private static ?CustomizeModel $model = NULL;

	const SALE_ORDER_FORM = 'sale-order-form';
	const SALE_DELIVERY_NOTE = 'sale-delivery-note';
	const SALE_INVOICE = 'sale-invoice';
	const SHOP_CONFIRMED_HOME = 'shop-confirmed-home';
	const SHOP_CONFIRMED_PLACE = 'shop-confirmed-place';

	public static function getSelection(): array {
		return Customize::model()->getProperties();
	}

	public static function model(): CustomizeModel {
		if(self::$model === NULL) {
			self::$model = new CustomizeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Customize::'.$failName, $arguments, $wrapper);
	}

}


class CustomizeModel extends \ModuleModel {

	protected string $module = 'mail\Customize';
	protected string $package = 'mail';
	protected string $table = 'mailCustomize';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'shop' => ['element32', 'shop\Shop', 'null' => TRUE, 'cast' => 'element'],
			'type' => ['enum', [\mail\Customize::SALE_ORDER_FORM, \mail\Customize::SALE_DELIVERY_NOTE, \mail\Customize::SALE_INVOICE, \mail\Customize::SHOP_CONFIRMED_HOME, \mail\Customize::SHOP_CONFIRMED_PLACE], 'cast' => 'enum'],
			'template' => ['text24', 'min' => 0, 'max' => NULL, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'shop', 'type', 'template'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'shop' => 'shop\Shop',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'type']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): CustomizeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CustomizeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CustomizeModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): CustomizeModel {
		return $this->where('farm', ...$data);
	}

	public function whereShop(...$data): CustomizeModel {
		return $this->where('shop', ...$data);
	}

	public function whereType(...$data): CustomizeModel {
		return $this->where('type', ...$data);
	}

	public function whereTemplate(...$data): CustomizeModel {
		return $this->where('template', ...$data);
	}


}


abstract class CustomizeCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Customize {

		$e = new Customize();

		if(empty($id)) {
			Customize::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Customize::getSelection();
		}

		if(Customize::model()
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
			$properties = Customize::getSelection();
		}

		if($sort !== NULL) {
			Customize::model()->sort($sort);
		}

		return Customize::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Customize {

		return new Customize(['id' => NULL]);

	}

	public static function create(Customize $e): void {

		Customize::model()->insert($e);

	}

	public static function update(Customize $e, array $properties): void {

		$e->expects(['id']);

		Customize::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Customize $e, array $properties): void {

		Customize::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Customize $e): void {

		$e->expects(['id']);

		Customize::model()->delete($e);

	}

}


class CustomizePage extends \ModulePage {

	protected string $module = 'mail\Customize';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CustomizeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CustomizeLib::getPropertiesUpdate()
		);
	}

}
?>