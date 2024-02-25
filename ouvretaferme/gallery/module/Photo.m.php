<?php
namespace gallery;

abstract class PhotoElement extends \Element {

	use \FilterElement;

	private static ?PhotoModel $model = NULL;

	public static function getSelection(): array {
		return Photo::model()->getProperties();
	}

	public static function model(): PhotoModel {
		if(self::$model === NULL) {
			self::$model = new PhotoModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Photo::'.$failName, $arguments, $wrapper);
	}

}


class PhotoModel extends \ModuleModel {

	protected string $module = 'gallery\Photo';
	protected string $package = 'gallery';
	protected string $table = 'galleryPhoto';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'sequence' => ['element32', 'production\Sequence', 'null' => TRUE, 'cast' => 'element'],
			'series' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'task' => ['element32', 'series\Task', 'null' => TRUE, 'cast' => 'element'],
			'author' => ['element32', 'user\User', 'cast' => 'element'],
			'hash' => ['textFixed', 'min' => 20, 'max' => 20, 'cast' => 'string'],
			'title' => ['text16', 'min' => 1, 'max' => 500, 'null' => TRUE, 'cast' => 'string'],
			'width' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'height' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'takenAt' => ['month', 'min' => '1900-01-01', 'max' => currentDate(), 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'sequence', 'series', 'task', 'author', 'hash', 'title', 'width', 'height', 'takenAt', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'sequence' => 'production\Sequence',
			'series' => 'series\Series',
			'task' => 'series\Task',
			'author' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['series'],
			['task'],
			['sequence']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'author' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): PhotoModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PhotoModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PhotoModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): PhotoModel {
		return $this->where('farm', ...$data);
	}

	public function whereSequence(...$data): PhotoModel {
		return $this->where('sequence', ...$data);
	}

	public function whereSeries(...$data): PhotoModel {
		return $this->where('series', ...$data);
	}

	public function whereTask(...$data): PhotoModel {
		return $this->where('task', ...$data);
	}

	public function whereAuthor(...$data): PhotoModel {
		return $this->where('author', ...$data);
	}

	public function whereHash(...$data): PhotoModel {
		return $this->where('hash', ...$data);
	}

	public function whereTitle(...$data): PhotoModel {
		return $this->where('title', ...$data);
	}

	public function whereWidth(...$data): PhotoModel {
		return $this->where('width', ...$data);
	}

	public function whereHeight(...$data): PhotoModel {
		return $this->where('height', ...$data);
	}

	public function whereTakenAt(...$data): PhotoModel {
		return $this->where('takenAt', ...$data);
	}

	public function whereCreatedAt(...$data): PhotoModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class PhotoCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Photo {

		$e = new Photo();

		if(empty($id)) {
			Photo::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Photo::getSelection();
		}

		if(Photo::model()
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
			$properties = Photo::getSelection();
		}

		if($sort !== NULL) {
			Photo::model()->sort($sort);
		}

		return Photo::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Photo {

		return new Photo(['id' => NULL]);

	}

	public static function create(Photo $e): void {

		Photo::model()->insert($e);

	}

	public static function update(Photo $e, array $properties): void {

		$e->expects(['id']);

		Photo::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Photo $e, array $properties): void {

		Photo::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Photo $e): void {

		$e->expects(['id']);

		Photo::model()->delete($e);

	}

}


class PhotoPage extends \ModulePage {

	protected string $module = 'gallery\Photo';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PhotoLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PhotoLib::getPropertiesUpdate()
		);
	}

}
?>