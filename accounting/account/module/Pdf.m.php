<?php
namespace account;

abstract class PdfElement extends \Element {

	use \FilterElement;

	private static ?PdfModel $model = NULL;

	public static function getSelection(): array {
		return Pdf::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): PdfModel {
		if(self::$model === NULL) {
			self::$model = new PdfModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Pdf::'.$failName, $arguments, $wrapper);
	}

}


class PdfModel extends \ModuleModel {

	protected string $module = 'account\Pdf';
	protected string $package = 'account';
	protected string $table = 'accountPdf';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'used' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'content' => ['element32', 'account\PdfContent', 'null' => TRUE, 'cast' => 'element'],
			'crc32' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'type' => ['text8', 'cast' => 'string'],
			'version' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'financialYear', 'name', 'used', 'content', 'crc32', 'type', 'version', 'createdAt'
		]);

		$this->propertiesToModule += [
			'financialYear' => 'account\FinancialYear',
			'content' => 'account\PdfContent',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['financialYear', 'type'],
			['content']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'used' :
				return 1;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): PdfModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PdfModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PdfModel {
		return $this->where('id', ...$data);
	}

	public function whereFinancialYear(...$data): PdfModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereName(...$data): PdfModel {
		return $this->where('name', ...$data);
	}

	public function whereUsed(...$data): PdfModel {
		return $this->where('used', ...$data);
	}

	public function whereContent(...$data): PdfModel {
		return $this->where('content', ...$data);
	}

	public function whereCrc32(...$data): PdfModel {
		return $this->where('crc32', ...$data);
	}

	public function whereType(...$data): PdfModel {
		return $this->where('type', ...$data);
	}

	public function whereVersion(...$data): PdfModel {
		return $this->where('version', ...$data);
	}

	public function whereCreatedAt(...$data): PdfModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class PdfCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Pdf {

		$e = new Pdf();

		if(empty($id)) {
			Pdf::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Pdf::getSelection();
		}

		if(Pdf::model()
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
			$properties = Pdf::getSelection();
		}

		if($sort !== NULL) {
			Pdf::model()->sort($sort);
		}

		return Pdf::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Pdf {

		return new Pdf(['id' => NULL]);

	}

	public static function create(Pdf $e): void {

		Pdf::model()->insert($e);

	}

	public static function update(Pdf $e, array $properties): void {

		$e->expects(['id']);

		Pdf::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Pdf $e, array $properties): void {

		Pdf::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Pdf $e): void {

		$e->expects(['id']);

		Pdf::model()->delete($e);

	}

}


class PdfPage extends \ModulePage {

	protected string $module = 'account\Pdf';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PdfLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PdfLib::getPropertiesUpdate()
		);
	}

}
?>
