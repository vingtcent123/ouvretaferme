<?php
namespace plant;

abstract class SizeElement extends \Element {

	use \FilterElement;

	private static ?SizeModel $model = NULL;

	public static function getSelection(): array {
		return Size::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): SizeModel {
		if(self::$model === NULL) {
			self::$model = new SizeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Size::'.$failName, $arguments, $wrapper);
	}

}


class SizeModel extends \ModuleModel {

	protected string $module = 'plant\Size';
	protected string $package = 'plant';
	protected string $table = 'plantSize';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'collate' => 'general', 'cast' => 'string'],
			'comment' => ['editor16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'plant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'yield' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['date', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'comment', 'plant', 'farm', 'yield', 'createdAt'
		]);

		$this->propertiesToModule += [
			'plant' => 'plant\Plant',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['plant']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'plant', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('CURDATE()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): SizeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SizeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SizeModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): SizeModel {
		return $this->where('name', ...$data);
	}

	public function whereComment(...$data): SizeModel {
		return $this->where('comment', ...$data);
	}

	public function wherePlant(...$data): SizeModel {
		return $this->where('plant', ...$data);
	}

	public function whereFarm(...$data): SizeModel {
		return $this->where('farm', ...$data);
	}

	public function whereYield(...$data): SizeModel {
		return $this->where('yield', ...$data);
	}

	public function whereCreatedAt(...$data): SizeModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class SizeCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Size {

		$e = new Size();

		if(empty($id)) {
			Size::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Size::getSelection();
		}

		if(Size::model()
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
			$properties = Size::getSelection();
		}

		if($sort !== NULL) {
			Size::model()->sort($sort);
		}

		return Size::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Size {

		return new Size(['id' => NULL]);

	}

	public static function create(Size $e): void {

		Size::model()->insert($e);

	}

	public static function update(Size $e, array $properties): void {

		$e->expects(['id']);

		Size::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Size $e, array $properties): void {

		Size::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Size $e): void {

		$e->expects(['id']);

		Size::model()->delete($e);

	}

}


class SizePage extends \ModulePage {

	protected string $module = 'plant\Size';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SizeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SizeLib::getPropertiesUpdate()
		);
	}

}
?>