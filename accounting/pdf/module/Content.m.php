<?php
namespace pdf;

abstract class ContentElement extends \Element {

	use \FilterElement;

	private static ?ContentModel $model = NULL;

	public static function getSelection(): array {
		return Content::model()->getProperties();
	}

	public static function model(): ContentModel {
		if(self::$model === NULL) {
			self::$model = new ContentModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Content::'.$failName, $arguments, $wrapper);
	}

}


class ContentModel extends \ModuleModel {

	protected string $module = 'pdf\Content';
	protected string $package = 'pdf';
	protected string $table = 'pdfContent';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'hash' => ['textFixed', 'min' => 20, 'max' => 20, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'hash', 'createdAt'
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

	public function select(...$fields): ContentModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ContentModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ContentModel {
		return $this->where('id', ...$data);
	}

	public function whereHash(...$data): ContentModel {
		return $this->where('hash', ...$data);
	}

	public function whereCreatedAt(...$data): ContentModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class ContentCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Content {

		$e = new Content();

		if(empty($id)) {
			Content::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Content::getSelection();
		}

		if(Content::model()
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
			$properties = Content::getSelection();
		}

		if($sort !== NULL) {
			Content::model()->sort($sort);
		}

		return Content::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Content {

		return new Content(['id' => NULL]);

	}

	public static function create(Content $e): void {

		Content::model()->insert($e);

	}

	public static function update(Content $e, array $properties): void {

		$e->expects(['id']);

		Content::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Content $e, array $properties): void {

		Content::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Content $e): void {

		$e->expects(['id']);

		Content::model()->delete($e);

	}

}


class ContentPage extends \ModulePage {

	protected string $module = 'pdf\Content';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ContentLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ContentLib::getPropertiesUpdate()
		);
	}

}
?>