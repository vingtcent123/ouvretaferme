<?php
namespace media;

abstract class MediaElement extends \Element {

	use \FilterElement;

	private static ?MediaModel $model = NULL;

	const ACTIVE = 'active';
	const SEARCHING = 'searching';
	const DELETED = 'deleted';

	public static function getSelection(): array {
		return Media::model()->getProperties();
	}

	public static function model(): MediaModel {
		if(self::$model === NULL) {
			self::$model = new MediaModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Media::'.$failName, $arguments, $wrapper);
	}

}


class MediaModel extends \ModuleModel {

	protected string $module = 'media\Media';
	protected string $package = 'media';
	protected string $table = 'media';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'type' => ['enum', \Setting::get('media\images'), 'cast' => 'enum'],
			'status' => ['enum', [\media\Media::ACTIVE, \media\Media::SEARCHING, \media\Media::DELETED], 'cast' => 'enum'],
			'hash' => ['textFixed', 'min' => 20, 'max' => 20, 'charset' => 'ascii', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'updatedAt', 'type', 'status', 'hash'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['status', 'updatedAt']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['type', 'hash']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'user' :
				return \user\ConnectionLib::getOnline();

			case 'updatedAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Media::ACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): MediaModel {
		return parent::select(...$fields);
	}

	public function where(...$data): MediaModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): MediaModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): MediaModel {
		return $this->where('user', ...$data);
	}

	public function whereUpdatedAt(...$data): MediaModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereType(...$data): MediaModel {
		return $this->where('type', ...$data);
	}

	public function whereStatus(...$data): MediaModel {
		return $this->where('status', ...$data);
	}

	public function whereHash(...$data): MediaModel {
		return $this->where('hash', ...$data);
	}


}


abstract class MediaCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Media {

		$e = new Media();

		if(empty($id)) {
			Media::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Media::getSelection();
		}

		if(Media::model()
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
			$properties = Media::getSelection();
		}

		if($sort !== NULL) {
			Media::model()->sort($sort);
		}

		return Media::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Media {

		return new Media(['id' => NULL]);

	}

	public static function create(Media $e): void {

		Media::model()->insert($e);

	}

	public static function update(Media $e, array $properties): void {

		$e->expects(['id']);

		Media::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Media $e, array $properties): void {

		Media::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Media $e): void {

		$e->expects(['id']);

		Media::model()->delete($e);

	}

}


class MediaPage extends \ModulePage {

	protected string $module = 'media\Media';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? MediaLib::getPropertiesCreate(),
		   $propertiesUpdate ?? MediaLib::getPropertiesUpdate()
		);
	}

}
?>