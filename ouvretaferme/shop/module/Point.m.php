<?php
namespace shop;

abstract class PointElement extends \Element {

	use \FilterElement;

	private static ?PointModel $model = NULL;

	const HOME = 'home';
	const PLACE = 'place';

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

	public static function getSelection(): array {
		return Point::model()->getProperties();
	}

	public static function model(): PointModel {
		if(self::$model === NULL) {
			self::$model = new PointModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Point::'.$failName, $arguments, $wrapper);
	}

}


class PointModel extends \ModuleModel {

	protected string $module = 'shop\Point';
	protected string $package = 'shop';
	protected string $table = 'shopPoint';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'type' => ['enum', [\shop\Point::HOME, \shop\Point::PLACE], 'cast' => 'enum'],
			'zone' => ['text16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'place' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'address' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'paymentCard' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'paymentTransfer' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'paymentOffline' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'orderMin' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'shipping' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'shippingUntil' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'status' => ['enum', [\shop\Point::ACTIVE, \shop\Point::INACTIVE], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'shop', 'farm', 'type', 'zone', 'name', 'description', 'place', 'address', 'paymentCard', 'paymentTransfer', 'paymentOffline', 'orderMin', 'shipping', 'shippingUntil', 'status', 'createdAt'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
			'farm' => 'farm\Farm',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['shop', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Point::ACTIVE;

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

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): PointModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PointModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PointModel {
		return $this->where('id', ...$data);
	}

	public function whereShop(...$data): PointModel {
		return $this->where('shop', ...$data);
	}

	public function whereFarm(...$data): PointModel {
		return $this->where('farm', ...$data);
	}

	public function whereType(...$data): PointModel {
		return $this->where('type', ...$data);
	}

	public function whereZone(...$data): PointModel {
		return $this->where('zone', ...$data);
	}

	public function whereName(...$data): PointModel {
		return $this->where('name', ...$data);
	}

	public function whereDescription(...$data): PointModel {
		return $this->where('description', ...$data);
	}

	public function wherePlace(...$data): PointModel {
		return $this->where('place', ...$data);
	}

	public function whereAddress(...$data): PointModel {
		return $this->where('address', ...$data);
	}

	public function wherePaymentCard(...$data): PointModel {
		return $this->where('paymentCard', ...$data);
	}

	public function wherePaymentTransfer(...$data): PointModel {
		return $this->where('paymentTransfer', ...$data);
	}

	public function wherePaymentOffline(...$data): PointModel {
		return $this->where('paymentOffline', ...$data);
	}

	public function whereOrderMin(...$data): PointModel {
		return $this->where('orderMin', ...$data);
	}

	public function whereShipping(...$data): PointModel {
		return $this->where('shipping', ...$data);
	}

	public function whereShippingUntil(...$data): PointModel {
		return $this->where('shippingUntil', ...$data);
	}

	public function whereStatus(...$data): PointModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): PointModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class PointCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Point {

		$e = new Point();

		if(empty($id)) {
			Point::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Point::getSelection();
		}

		if(Point::model()
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
			$properties = Point::getSelection();
		}

		if($sort !== NULL) {
			Point::model()->sort($sort);
		}

		return Point::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Point {

		return new Point(['id' => NULL]);

	}

	public static function create(Point $e): void {

		Point::model()->insert($e);

	}

	public static function update(Point $e, array $properties): void {

		$e->expects(['id']);

		Point::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Point $e, array $properties): void {

		Point::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Point $e): void {

		$e->expects(['id']);

		Point::model()->delete($e);

	}

}


class PointPage extends \ModulePage {

	protected string $module = 'shop\Point';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PointLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PointLib::getPropertiesUpdate()
		);
	}

}
?>