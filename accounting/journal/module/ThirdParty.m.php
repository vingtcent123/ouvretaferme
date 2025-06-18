<?php
namespace journal;

abstract class ThirdPartyElement extends \Element {

	use \FilterElement;

	private static ?ThirdPartyModel $model = NULL;

	public static function getSelection(): array {
		return ThirdParty::model()->getProperties();
	}

	public static function model(): ThirdPartyModel {
		if(self::$model === NULL) {
			self::$model = new ThirdPartyModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('ThirdParty::'.$failName, $arguments, $wrapper);
	}

}


class ThirdPartyModel extends \ModuleModel {

	protected string $module = 'journal\ThirdParty';
	protected string $package = 'journal';
	protected string $table = 'journalThirdParty';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'unique' => TRUE, 'cast' => 'string'],
			'clientAccountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'supplierAccountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'clientAccountLabel', 'supplierAccountLabel'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['name']
		]);

	}

	public function select(...$fields): ThirdPartyModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ThirdPartyModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ThirdPartyModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): ThirdPartyModel {
		return $this->where('name', ...$data);
	}

	public function whereClientAccountLabel(...$data): ThirdPartyModel {
		return $this->where('clientAccountLabel', ...$data);
	}

	public function whereSupplierAccountLabel(...$data): ThirdPartyModel {
		return $this->where('supplierAccountLabel', ...$data);
	}


}


abstract class ThirdPartyCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): ThirdParty {

		$e = new ThirdParty();

		if(empty($id)) {
			ThirdParty::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = ThirdParty::getSelection();
		}

		if(ThirdParty::model()
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
			$properties = ThirdParty::getSelection();
		}

		if($sort !== NULL) {
			ThirdParty::model()->sort($sort);
		}

		return ThirdParty::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): ThirdParty {

		return new ThirdParty(['id' => NULL]);

	}

	public static function create(ThirdParty $e): void {

		ThirdParty::model()->insert($e);

	}

	public static function update(ThirdParty $e, array $properties): void {

		$e->expects(['id']);

		ThirdParty::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, ThirdParty $e, array $properties): void {

		ThirdParty::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(ThirdParty $e): void {

		$e->expects(['id']);

		ThirdParty::model()->delete($e);

	}

}


class ThirdPartyPage extends \ModulePage {

	protected string $module = 'journal\ThirdParty';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ThirdPartyLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ThirdPartyLib::getPropertiesUpdate()
		);
	}

}
?>