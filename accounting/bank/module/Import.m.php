<?php
namespace bank;

abstract class ImportElement extends \Element {

	use \FilterElement;

	private static ?ImportModel $model = NULL;

	const PROCESSING = 'processing';
	const FULL = 'full';
	const PARTIAL = 'partial';
	const NONE = 'none';
	const ERROR = 'error';

	public static function getSelection(): array {
		return Import::model()->getProperties();
	}

	public static function model(): ImportModel {
		if(self::$model === NULL) {
			self::$model = new ImportModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Import::'.$failName, $arguments, $wrapper);
	}

}


class ImportModel extends \ModuleModel {

	protected string $module = 'bank\Import';
	protected string $package = 'bank';
	protected string $table = 'bankImport';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'filename' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'startDate' => ['datetime', 'cast' => 'string'],
			'endDate' => ['datetime', 'cast' => 'string'],
			'result' => ['json', 'cast' => 'array'],
			'status' => ['enum', [\bank\Import::PROCESSING, \bank\Import::FULL, \bank\Import::PARTIAL, \bank\Import::NONE, \bank\Import::ERROR], 'cast' => 'enum'],
			'account' => ['element32', 'bank\Account', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'processedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'filename', 'startDate', 'endDate', 'result', 'status', 'account', 'createdAt', 'processedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'account' => 'bank\Account',
			'createdBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'result' :
				return [];

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'result' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'result' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): ImportModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ImportModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ImportModel {
		return $this->where('id', ...$data);
	}

	public function whereFilename(...$data): ImportModel {
		return $this->where('filename', ...$data);
	}

	public function whereStartDate(...$data): ImportModel {
		return $this->where('startDate', ...$data);
	}

	public function whereEndDate(...$data): ImportModel {
		return $this->where('endDate', ...$data);
	}

	public function whereResult(...$data): ImportModel {
		return $this->where('result', ...$data);
	}

	public function whereStatus(...$data): ImportModel {
		return $this->where('status', ...$data);
	}

	public function whereAccount(...$data): ImportModel {
		return $this->where('account', ...$data);
	}

	public function whereCreatedAt(...$data): ImportModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereProcessedAt(...$data): ImportModel {
		return $this->where('processedAt', ...$data);
	}

	public function whereCreatedBy(...$data): ImportModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class ImportCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Import {

		$e = new Import();

		if(empty($id)) {
			Import::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Import::getSelection();
		}

		if(Import::model()
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
			$properties = Import::getSelection();
		}

		if($sort !== NULL) {
			Import::model()->sort($sort);
		}

		return Import::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Import {

		return new Import(['id' => NULL]);

	}

	public static function create(Import $e): void {

		Import::model()->insert($e);

	}

	public static function update(Import $e, array $properties): void {

		$e->expects(['id']);

		Import::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Import $e, array $properties): void {

		Import::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Import $e): void {

		$e->expects(['id']);

		Import::model()->delete($e);

	}

}


class ImportPage extends \ModulePage {

	protected string $module = 'bank\Import';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ImportLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ImportLib::getPropertiesUpdate()
		);
	}

}
?>