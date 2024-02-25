<?php
namespace selling;

abstract class PdfContentElement extends \Element {

	use \FilterElement;

	private static ?PdfContentModel $model = NULL;

	public static function getSelection(): array {
		return PdfContent::model()->getProperties();
	}

	public static function model(): PdfContentModel {
		if(self::$model === NULL) {
			self::$model = new PdfContentModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('PdfContent::'.$failName, $arguments, $wrapper);
	}

}


class PdfContentModel extends \ModuleModel {

	protected string $module = 'selling\PdfContent';
	protected string $package = 'selling';
	protected string $table = 'sellingPdfContent';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'binary' => ['binary32', 'cast' => 'binary'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'binary', 'createdAt'
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

	public function select(...$fields): PdfContentModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PdfContentModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PdfContentModel {
		return $this->where('id', ...$data);
	}

	public function whereBinary(...$data): PdfContentModel {
		return $this->where('binary', ...$data);
	}

	public function whereCreatedAt(...$data): PdfContentModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class PdfContentCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): PdfContent {

		$e = new PdfContent();

		if(empty($id)) {
			PdfContent::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = PdfContent::getSelection();
		}

		if(PdfContent::model()
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
			$properties = PdfContent::getSelection();
		}

		if($sort !== NULL) {
			PdfContent::model()->sort($sort);
		}

		return PdfContent::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): PdfContent {

		return new PdfContent(['id' => NULL]);

	}

	public static function create(PdfContent $e): void {

		PdfContent::model()->insert($e);

	}

	public static function update(PdfContent $e, array $properties): void {

		$e->expects(['id']);

		PdfContent::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, PdfContent $e, array $properties): void {

		PdfContent::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(PdfContent $e): void {

		$e->expects(['id']);

		PdfContent::model()->delete($e);

	}

}


class PdfContentPage extends \ModulePage {

	protected string $module = 'selling\PdfContent';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PdfContentLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PdfContentLib::getPropertiesUpdate()
		);
	}

}
?>