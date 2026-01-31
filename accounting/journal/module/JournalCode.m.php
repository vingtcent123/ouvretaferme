<?php
namespace journal;

abstract class JournalCodeElement extends \Element {

	use \FilterElement;

	private static ?JournalCodeModel $model = NULL;

	public static function getSelection(): array {
		return JournalCode::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): JournalCodeModel {
		if(self::$model === NULL) {
			self::$model = new JournalCodeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('JournalCode::'.$failName, $arguments, $wrapper);
	}

}


class JournalCodeModel extends \ModuleModel {

	protected string $module = 'journal\JournalCode';
	protected string $package = 'journal';
	protected string $table = 'journalJournalCode';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'code' => ['text8', 'min' => 2, 'max' => 4, 'unique' => TRUE, 'cast' => 'string'],
			'isCustom' => ['bool', 'cast' => 'bool'],
			'color' => ['color', 'null' => TRUE, 'cast' => 'string'],
			'isReversable' => ['bool', 'cast' => 'bool'],
			'isDisplayed' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'code', 'isCustom', 'color', 'isReversable', 'isDisplayed'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['code']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'isCustom' :
				return TRUE;

			case 'isReversable' :
				return FALSE;

			case 'isDisplayed' :
				return TRUE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): JournalCodeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): JournalCodeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): JournalCodeModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): JournalCodeModel {
		return $this->where('name', ...$data);
	}

	public function whereCode(...$data): JournalCodeModel {
		return $this->where('code', ...$data);
	}

	public function whereIsCustom(...$data): JournalCodeModel {
		return $this->where('isCustom', ...$data);
	}

	public function whereColor(...$data): JournalCodeModel {
		return $this->where('color', ...$data);
	}

	public function whereIsReversable(...$data): JournalCodeModel {
		return $this->where('isReversable', ...$data);
	}

	public function whereIsDisplayed(...$data): JournalCodeModel {
		return $this->where('isDisplayed', ...$data);
	}


}


abstract class JournalCodeCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): JournalCode {

		$e = new JournalCode();

		if(empty($id)) {
			JournalCode::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = JournalCode::getSelection();
		}

		if(JournalCode::model()
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
			$properties = JournalCode::getSelection();
		}

		if($sort !== NULL) {
			JournalCode::model()->sort($sort);
		}

		return JournalCode::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): JournalCode {

		return new JournalCode($properties);

	}

	public static function create(JournalCode $e): void {

		JournalCode::model()->insert($e);

	}

	public static function update(JournalCode $e, array $properties): void {

		$e->expects(['id']);

		JournalCode::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, JournalCode $e, array $properties): void {

		JournalCode::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(JournalCode $e): void {

		$e->expects(['id']);

		JournalCode::model()->delete($e);

	}

}


class JournalCodePage extends \ModulePage {

	protected string $module = 'journal\JournalCode';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? JournalCodeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? JournalCodeLib::getPropertiesUpdate()
		);
	}

}
?>