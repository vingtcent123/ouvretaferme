<?php
namespace shop;

abstract class DateElement extends \Element {

	use \FilterElement;

	private static ?DateModel $model = NULL;

	const ACTIVE = 'active';
	const CLOSED = 'closed';

	public static function getSelection(): array {
		return Date::model()->getProperties();
	}

	public static function model(): DateModel {
		if(self::$model === NULL) {
			self::$model = new DateModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Date::'.$failName, $arguments, $wrapper);
	}

}


class DateModel extends \ModuleModel {

	protected string $module = 'shop\Date';
	protected string $package = 'shop';
	protected string $table = 'shopDate';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'status' => ['enum', [\shop\Date::ACTIVE, \shop\Date::CLOSED], 'cast' => 'enum'],
			'orderStartAt' => ['datetime', 'cast' => 'string'],
			'orderEndAt' => ['datetime', 'cast' => 'string'],
			'points' => ['json', 'cast' => 'array'],
			'deliveryDate' => ['date', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'createdAt', 'shop', 'farm', 'status', 'orderStartAt', 'orderEndAt', 'points', 'deliveryDate'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['status']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Date::ACTIVE;

			case 'points' :
				return [];

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'points' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'points' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): DateModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DateModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DateModel {
		return $this->where('id', ...$data);
	}

	public function whereCreatedAt(...$data): DateModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereShop(...$data): DateModel {
		return $this->where('shop', ...$data);
	}

	public function whereFarm(...$data): DateModel {
		return $this->where('farm', ...$data);
	}

	public function whereStatus(...$data): DateModel {
		return $this->where('status', ...$data);
	}

	public function whereOrderStartAt(...$data): DateModel {
		return $this->where('orderStartAt', ...$data);
	}

	public function whereOrderEndAt(...$data): DateModel {
		return $this->where('orderEndAt', ...$data);
	}

	public function wherePoints(...$data): DateModel {
		return $this->where('points', ...$data);
	}

	public function whereDeliveryDate(...$data): DateModel {
		return $this->where('deliveryDate', ...$data);
	}


}


abstract class DateCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Date {

		$e = new Date();

		if(empty($id)) {
			Date::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Date::getSelection();
		}

		if(Date::model()
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
			$properties = Date::getSelection();
		}

		if($sort !== NULL) {
			Date::model()->sort($sort);
		}

		return Date::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Date {

		return new Date(['id' => NULL]);

	}

	public static function create(Date $e): void {

		Date::model()->insert($e);

	}

	public static function update(Date $e, array $properties): void {

		$e->expects(['id']);

		Date::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Date $e, array $properties): void {

		Date::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Date $e): void {

		$e->expects(['id']);

		Date::model()->delete($e);

	}

}


class DatePage extends \ModulePage {

	protected string $module = 'shop\Date';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DateLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DateLib::getPropertiesUpdate()
		);
	}

}
?>