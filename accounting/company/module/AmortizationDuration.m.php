<?php
namespace company;

abstract class AmortizationDurationElement extends \Element {

	use \FilterElement;

	private static ?AmortizationDurationModel $model = NULL;

	public static function getSelection(): array {
		return AmortizationDuration::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): AmortizationDurationModel {
		if(self::$model === NULL) {
			self::$model = new AmortizationDurationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('AmortizationDuration::'.$failName, $arguments, $wrapper);
	}

}


class AmortizationDurationModel extends \ModuleModel {

	protected string $module = 'company\AmortizationDuration';
	protected string $package = 'company';
	protected string $table = 'companyAmortizationDuration';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'class' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'durationMin' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'durationMax' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'class', 'durationMin', 'durationMax'
		]);

	}

	public function select(...$fields): AmortizationDurationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): AmortizationDurationModel {
		return parent::where(...$data);
	}

	public function whereClass(...$data): AmortizationDurationModel {
		return $this->where('class', ...$data);
	}

	public function whereDurationMin(...$data): AmortizationDurationModel {
		return $this->where('durationMin', ...$data);
	}

	public function whereDurationMax(...$data): AmortizationDurationModel {
		return $this->where('durationMax', ...$data);
	}


}


abstract class AmortizationDurationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): AmortizationDuration {

		$e = new AmortizationDuration();

		if(empty($id)) {
			AmortizationDuration::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = AmortizationDuration::getSelection();
		}

		if(AmortizationDuration::model()
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
			$properties = AmortizationDuration::getSelection();
		}

		if($sort !== NULL) {
			AmortizationDuration::model()->sort($sort);
		}

		return AmortizationDuration::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): AmortizationDuration {

		return new AmortizationDuration($properties);

	}

	public static function create(AmortizationDuration $e): void {

		AmortizationDuration::model()->insert($e);

	}

	public static function update(AmortizationDuration $e, array $properties): void {

		$e->expects(['id']);

		AmortizationDuration::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, AmortizationDuration $e, array $properties): void {

		AmortizationDuration::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(AmortizationDuration $e): void {

		$e->expects(['id']);

		AmortizationDuration::model()->delete($e);

	}

}


class AmortizationDurationPage extends \ModulePage {

	protected string $module = 'company\AmortizationDuration';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? AmortizationDurationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? AmortizationDurationLib::getPropertiesUpdate()
		);
	}

}
?>