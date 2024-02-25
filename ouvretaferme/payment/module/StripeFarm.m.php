<?php
namespace payment;

abstract class StripeFarmElement extends \Element {

	use \FilterElement;

	private static ?StripeFarmModel $model = NULL;

	public static function getSelection(): array {
		return StripeFarm::model()->getProperties();
	}

	public static function model(): StripeFarmModel {
		if(self::$model === NULL) {
			self::$model = new StripeFarmModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('StripeFarm::'.$failName, $arguments, $wrapper);
	}

}


class StripeFarmModel extends \ModuleModel {

	protected string $module = 'payment\StripeFarm';
	protected string $package = 'payment';
	protected string $table = 'paymentStripeFarm';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'unique' => TRUE, 'cast' => 'element'],
			'apiSecretKey' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'apiSecretKeyTest' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'webhookSecretKey' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'webhookSecretKeyTest' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'apiSecretKey', 'apiSecretKeyTest', 'webhookSecretKey', 'webhookSecretKeyTest', 'createdBy', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'createdBy' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): StripeFarmModel {
		return parent::select(...$fields);
	}

	public function where(...$data): StripeFarmModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): StripeFarmModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): StripeFarmModel {
		return $this->where('farm', ...$data);
	}

	public function whereApiSecretKey(...$data): StripeFarmModel {
		return $this->where('apiSecretKey', ...$data);
	}

	public function whereApiSecretKeyTest(...$data): StripeFarmModel {
		return $this->where('apiSecretKeyTest', ...$data);
	}

	public function whereWebhookSecretKey(...$data): StripeFarmModel {
		return $this->where('webhookSecretKey', ...$data);
	}

	public function whereWebhookSecretKeyTest(...$data): StripeFarmModel {
		return $this->where('webhookSecretKeyTest', ...$data);
	}

	public function whereCreatedBy(...$data): StripeFarmModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereCreatedAt(...$data): StripeFarmModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class StripeFarmCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): StripeFarm {

		$e = new StripeFarm();

		if(empty($id)) {
			StripeFarm::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = StripeFarm::getSelection();
		}

		if(StripeFarm::model()
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
			$properties = StripeFarm::getSelection();
		}

		if($sort !== NULL) {
			StripeFarm::model()->sort($sort);
		}

		return StripeFarm::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): StripeFarm {

		return new StripeFarm(['id' => NULL]);

	}

	public static function create(StripeFarm $e): void {

		StripeFarm::model()->insert($e);

	}

	public static function update(StripeFarm $e, array $properties): void {

		$e->expects(['id']);

		StripeFarm::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, StripeFarm $e, array $properties): void {

		StripeFarm::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(StripeFarm $e): void {

		$e->expects(['id']);

		StripeFarm::model()->delete($e);

	}

}


class StripeFarmPage extends \ModulePage {

	protected string $module = 'payment\StripeFarm';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? StripeFarmLib::getPropertiesCreate(),
		   $propertiesUpdate ?? StripeFarmLib::getPropertiesUpdate()
		);
	}

}
?>