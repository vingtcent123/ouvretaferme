<?php
namespace account;

abstract class FinancialYearDocumentElement extends \Element {

	use \FilterElement;

	private static ?FinancialYearDocumentModel $model = NULL;

	const WAITING = 'waiting';
	const NOW = 'now';
	const PROCESSING = 'processing';
	const FAIL = 'fail';
	const SUCCESS = 'success';

	public static function getSelection(): array {
		return FinancialYearDocument::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): FinancialYearDocumentModel {
		if(self::$model === NULL) {
			self::$model = new FinancialYearDocumentModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('FinancialYearDocument::'.$failName, $arguments, $wrapper);
	}

}


class FinancialYearDocumentModel extends \ModuleModel {

	protected string $module = 'account\FinancialYearDocument';
	protected string $package = 'account';
	protected string $table = 'accountFinancialYearDocument';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'type' => ['text8', 'cast' => 'string'],
			'generation' => ['enum', [\account\FinancialYearDocument::WAITING, \account\FinancialYearDocument::NOW, \account\FinancialYearDocument::PROCESSING, \account\FinancialYearDocument::FAIL, \account\FinancialYearDocument::SUCCESS], 'null' => TRUE, 'cast' => 'enum'],
			'generationAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'content' => ['element32', 'account\PdfContent', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'financialYear', 'type', 'generation', 'generationAt', 'content', 'createdAt'
		]);

		$this->propertiesToModule += [
			'financialYear' => 'account\FinancialYear',
			'content' => 'account\PdfContent',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['financialYear', 'type']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'generation' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): FinancialYearDocumentModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FinancialYearDocumentModel {
		return parent::where(...$data);
	}

	public function whereFinancialYear(...$data): FinancialYearDocumentModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereType(...$data): FinancialYearDocumentModel {
		return $this->where('type', ...$data);
	}

	public function whereGeneration(...$data): FinancialYearDocumentModel {
		return $this->where('generation', ...$data);
	}

	public function whereGenerationAt(...$data): FinancialYearDocumentModel {
		return $this->where('generationAt', ...$data);
	}

	public function whereContent(...$data): FinancialYearDocumentModel {
		return $this->where('content', ...$data);
	}

	public function whereCreatedAt(...$data): FinancialYearDocumentModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class FinancialYearDocumentCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): FinancialYearDocument {

		$e = new FinancialYearDocument();

		if(empty($id)) {
			FinancialYearDocument::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = FinancialYearDocument::getSelection();
		}

		if(FinancialYearDocument::model()
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
			$properties = FinancialYearDocument::getSelection();
		}

		if($sort !== NULL) {
			FinancialYearDocument::model()->sort($sort);
		}

		return FinancialYearDocument::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): FinancialYearDocument {

		return new FinancialYearDocument($properties);

	}

	public static function create(FinancialYearDocument $e): void {

		FinancialYearDocument::model()->insert($e);

	}

	public static function update(FinancialYearDocument $e, array $properties): void {

		$e->expects(['id']);

		FinancialYearDocument::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, FinancialYearDocument $e, array $properties): void {

		FinancialYearDocument::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(FinancialYearDocument $e): void {

		$e->expects(['id']);

		FinancialYearDocument::model()->delete($e);

	}

}


class FinancialYearDocumentPage extends \ModulePage {

	protected string $module = 'account\FinancialYearDocument';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FinancialYearDocumentLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FinancialYearDocumentLib::getPropertiesUpdate()
		);
	}

}
?>