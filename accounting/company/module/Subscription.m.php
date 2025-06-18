<?php
namespace company;

abstract class SubscriptionElement extends \Element {

	use \FilterElement;

	private static ?SubscriptionModel $model = NULL;

	const ACCOUNTING = 'accounting';
	const PRODUCTION = 'production';
	const SALES = 'sales';

	public static function getSelection(): array {
		return Subscription::model()->getProperties();
	}

	public static function model(): SubscriptionModel {
		if(self::$model === NULL) {
			self::$model = new SubscriptionModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Subscription::'.$failName, $arguments, $wrapper);
	}

}


class SubscriptionModel extends \ModuleModel {

	protected string $module = 'company\Subscription';
	protected string $package = 'company';
	protected string $table = 'companySubscription';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'company' => ['element32', 'company\Company', 'cast' => 'element'],
			'type' => ['enum', [\company\Subscription::ACCOUNTING, \company\Subscription::PRODUCTION, \company\Subscription::SALES], 'cast' => 'enum'],
			'startsAt' => ['date', 'cast' => 'string'],
			'endsAt' => ['date', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'company', 'type', 'startsAt', 'endsAt', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'company' => 'company\Company',
			'createdBy' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['company', 'type']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): SubscriptionModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SubscriptionModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SubscriptionModel {
		return $this->where('id', ...$data);
	}

	public function whereCompany(...$data): SubscriptionModel {
		return $this->where('company', ...$data);
	}

	public function whereType(...$data): SubscriptionModel {
		return $this->where('type', ...$data);
	}

	public function whereStartsAt(...$data): SubscriptionModel {
		return $this->where('startsAt', ...$data);
	}

	public function whereEndsAt(...$data): SubscriptionModel {
		return $this->where('endsAt', ...$data);
	}

	public function whereCreatedAt(...$data): SubscriptionModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): SubscriptionModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereCreatedBy(...$data): SubscriptionModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class SubscriptionCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Subscription {

		$e = new Subscription();

		if(empty($id)) {
			Subscription::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Subscription::getSelection();
		}

		if(Subscription::model()
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
			$properties = Subscription::getSelection();
		}

		if($sort !== NULL) {
			Subscription::model()->sort($sort);
		}

		return Subscription::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Subscription {

		return new Subscription(['id' => NULL]);

	}

	public static function create(Subscription $e): void {

		Subscription::model()->insert($e);

	}

	public static function update(Subscription $e, array $properties): void {

		$e->expects(['id']);

		Subscription::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Subscription $e, array $properties): void {

		Subscription::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Subscription $e): void {

		$e->expects(['id']);

		Subscription::model()->delete($e);

	}

}


class SubscriptionPage extends \ModulePage {

	protected string $module = 'company\Subscription';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SubscriptionLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SubscriptionLib::getPropertiesUpdate()
		);
	}

}
?>