<?php
namespace journal;

abstract class DocumentElement extends \Element {

	use \FilterElement;

	private static ?DocumentModel $model = NULL;

	public static function getSelection(): array {
		return Document::model()->getProperties();
	}

	public static function model(): DocumentModel {
		if(self::$model === NULL) {
			self::$model = new DocumentModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Document::'.$failName, $arguments, $wrapper);
	}

}


class DocumentModel extends \ModuleModel {

	protected string $module = 'journal\Document';
	protected string $package = 'journal';
	protected string $table = 'journalDocument';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'number' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'document' => ['int32', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'number', 'document'
		]);

	}

	public function select(...$fields): DocumentModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DocumentModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DocumentModel {
		return $this->where('id', ...$data);
	}

	public function whereNumber(...$data): DocumentModel {
		return $this->where('number', ...$data);
	}

	public function whereDocument(...$data): DocumentModel {
		return $this->where('document', ...$data);
	}


}


abstract class DocumentCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Document {

		$e = new Document();

		if(empty($id)) {
			Document::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Document::getSelection();
		}

		if(Document::model()
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
			$properties = Document::getSelection();
		}

		if($sort !== NULL) {
			Document::model()->sort($sort);
		}

		return Document::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Document {

		return new Document(['id' => NULL]);

	}

	public static function create(Document $e): void {

		Document::model()->insert($e);

	}

	public static function update(Document $e, array $properties): void {

		$e->expects(['id']);

		Document::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Document $e, array $properties): void {

		Document::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Document $e): void {

		$e->expects(['id']);

		Document::model()->delete($e);

	}

}


class DocumentPage extends \ModulePage {

	protected string $module = 'journal\Document';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DocumentLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DocumentLib::getPropertiesUpdate()
		);
	}

}
?>