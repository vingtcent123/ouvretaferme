<?php
namespace account;

abstract class ImportElement extends \Element {

	use \FilterElement;

	private static ?ImportModel $model = NULL;

	const CREATED = 'created';
	const WAITING = 'waiting';
	const IN_PROGRESS = 'in-progress';
	const FEEDBACK_REQUESTED = 'feedback-requested';
	const FEEDBACK_TO_TREAT = 'feedback-to-treat';
	const DONE = 'done';
	const CANCELLED = 'cancelled';

	const OPEN = 'open';
	const CLOSED = 'closed';

	const DATES = 1;

	public static function getSelection(): array {
		return Import::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'account\Import';
	protected string $package = 'account';
	protected string $table = 'accountImport';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'filename' => ['text8', 'cast' => 'string'],
			'status' => ['enum', [\account\Import::CREATED, \account\Import::WAITING, \account\Import::IN_PROGRESS, \account\Import::FEEDBACK_REQUESTED, \account\Import::FEEDBACK_TO_TREAT, \account\Import::DONE, \account\Import::CANCELLED], 'cast' => 'enum'],
			'financialYearStatus' => ['enum', [\account\Import::OPEN, \account\Import::CLOSED], 'cast' => 'enum'],
			'errors' => ['set', [\account\Import::DATES], 'null' => TRUE, 'cast' => 'set'],
			'delimiter' => ['textFixed', 'min' => 1, 'max' => 1, 'null' => TRUE, 'cast' => 'string'],
			'content' => ['text32', 'cast' => 'string'],
			'rules' => ['json', 'cast' => 'array'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'financialYear', 'filename', 'status', 'financialYearStatus', 'errors', 'delimiter', 'content', 'rules', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'financialYear' => 'account\FinancialYear',
			'createdBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Import::CREATED;

			case 'financialYearStatus' :
				return Import::OPEN;

			case 'rules' :
				return [];

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

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'financialYearStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'rules' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'rules' :
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

	public function whereFinancialYear(...$data): ImportModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereFilename(...$data): ImportModel {
		return $this->where('filename', ...$data);
	}

	public function whereStatus(...$data): ImportModel {
		return $this->where('status', ...$data);
	}

	public function whereFinancialYearStatus(...$data): ImportModel {
		return $this->where('financialYearStatus', ...$data);
	}

	public function whereErrors(...$data): ImportModel {
		return $this->where('errors', ...$data);
	}

	public function whereDelimiter(...$data): ImportModel {
		return $this->where('delimiter', ...$data);
	}

	public function whereContent(...$data): ImportModel {
		return $this->where('content', ...$data);
	}

	public function whereRules(...$data): ImportModel {
		return $this->where('rules', ...$data);
	}

	public function whereCreatedAt(...$data): ImportModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): ImportModel {
		return $this->where('updatedAt', ...$data);
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

	protected string $module = 'account\Import';

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