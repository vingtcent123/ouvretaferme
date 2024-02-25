<?php
namespace storage;

abstract class BufferElement extends \Element {

	use \FilterElement;

	private static ?BufferModel $model = NULL;

	public static function getSelection(): array {
		return Buffer::model()->getProperties();
	}

	public static function model(): BufferModel {
		if(self::$model === NULL) {
			self::$model = new BufferModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Buffer::'.$failName, $arguments, $wrapper);
	}

}


class BufferModel extends \ModuleModel {

	protected string $module = 'storage\Buffer';
	protected string $package = 'storage';
	protected string $table = 'storageBuffer';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'type' => ['textFixed', 'min' => 0, 'max' => 20, 'charset' => 'ascii', 'cast' => 'string'],
			'basename' => ['textFixed', 'min' => 0, 'max' => 100, 'charset' => 'ascii', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'type', 'basename'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['type', 'basename']
		]);

	}

	public function select(...$fields): BufferModel {
		return parent::select(...$fields);
	}

	public function where(...$data): BufferModel {
		return parent::where(...$data);
	}

	public function whereType(...$data): BufferModel {
		return $this->where('type', ...$data);
	}

	public function whereBasename(...$data): BufferModel {
		return $this->where('basename', ...$data);
	}


}


abstract class BufferCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Buffer {

		$e = new Buffer();

		if(empty($id)) {
			Buffer::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Buffer::getSelection();
		}

		if(Buffer::model()
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
			$properties = Buffer::getSelection();
		}

		if($sort !== NULL) {
			Buffer::model()->sort($sort);
		}

		return Buffer::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Buffer {

		return new Buffer(['id' => NULL]);

	}

	public static function create(Buffer $e): void {

		Buffer::model()->insert($e);

	}

	public static function update(Buffer $e, array $properties): void {

		$e->expects(['id']);

		Buffer::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Buffer $e, array $properties): void {

		Buffer::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Buffer $e): void {

		$e->expects(['id']);

		Buffer::model()->delete($e);

	}

}


class BufferPage extends \ModulePage {

	protected string $module = 'storage\Buffer';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? BufferLib::getPropertiesCreate(),
		   $propertiesUpdate ?? BufferLib::getPropertiesUpdate()
		);
	}

}
?>