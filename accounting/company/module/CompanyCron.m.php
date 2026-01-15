<?php
namespace company;

abstract class CompanyCronElement extends \Element {

	use \FilterElement;

	private static ?CompanyCronModel $model = NULL;

	const WAITING = 'waiting';
	const PROCESSING = 'processing';
	const FAIL = 'fail';

	public static function getSelection(): array {
		return CompanyCron::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): CompanyCronModel {
		if(self::$model === NULL) {
			self::$model = new CompanyCronModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('CompanyCron::'.$failName, $arguments, $wrapper);
	}

}


class CompanyCronModel extends \ModuleModel {

	protected string $module = 'company\CompanyCron';
	protected string $package = 'company';
	protected string $table = 'companyCompanyCron';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'action' => ['text8', 'cast' => 'string'],
			'status' => ['enum', [\company\CompanyCron::WAITING, \company\CompanyCron::PROCESSING, \company\CompanyCron::FAIL], 'null' => TRUE, 'cast' => 'enum'],
			'element' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'action', 'status', 'element'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'action', 'element']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): CompanyCronModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CompanyCronModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CompanyCronModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): CompanyCronModel {
		return $this->where('farm', ...$data);
	}

	public function whereAction(...$data): CompanyCronModel {
		return $this->where('action', ...$data);
	}

	public function whereStatus(...$data): CompanyCronModel {
		return $this->where('status', ...$data);
	}

	public function whereElement(...$data): CompanyCronModel {
		return $this->where('element', ...$data);
	}


}


abstract class CompanyCronCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): CompanyCron {

		$e = new CompanyCron();

		if(empty($id)) {
			CompanyCron::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = CompanyCron::getSelection();
		}

		if(CompanyCron::model()
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
			$properties = CompanyCron::getSelection();
		}

		if($sort !== NULL) {
			CompanyCron::model()->sort($sort);
		}

		return CompanyCron::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): CompanyCron {

		return new CompanyCron(['id' => NULL]);

	}

	public static function create(CompanyCron $e): void {

		CompanyCron::model()->insert($e);

	}

	public static function update(CompanyCron $e, array $properties): void {

		$e->expects(['id']);

		CompanyCron::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, CompanyCron $e, array $properties): void {

		CompanyCron::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(CompanyCron $e): void {

		$e->expects(['id']);

		CompanyCron::model()->delete($e);

	}

}


class CompanyCronPage extends \ModulePage {

	protected string $module = 'company\CompanyCron';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CompanyCronLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CompanyCronLib::getPropertiesUpdate()
		);
	}

}
?>