<?php
namespace production;

abstract class SliceElement extends \Element {

	use \FilterElement;

	private static ?SliceModel $model = NULL;

	public static function getSelection(): array {
		return Slice::model()->getProperties();
	}

	public static function model(): SliceModel {
		if(self::$model === NULL) {
			self::$model = new SliceModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Slice::'.$failName, $arguments, $wrapper);
	}

}


class SliceModel extends \ModuleModel {

	protected string $module = 'production\Slice';
	protected string $package = 'production';
	protected string $table = 'productionSlice';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'sequence' => ['element32', 'production\Sequence', 'cast' => 'element'],
			'crop' => ['element32', 'production\Crop', 'cast' => 'element'],
			'plant' => ['element32', 'plant\Plant', 'cast' => 'element'],
			'variety' => ['element32', 'plant\Variety', 'cast' => 'element'],
			'partPercent' => ['float32', 'min' => 0.0, 'max' => 100.0, 'null' => TRUE, 'cast' => 'float'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'sequence', 'crop', 'plant', 'variety', 'partPercent'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'sequence' => 'production\Sequence',
			'crop' => 'production\Crop',
			'plant' => 'plant\Plant',
			'variety' => 'plant\Variety',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['crop', 'variety']
		]);

	}

	public function select(...$fields): SliceModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SliceModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SliceModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): SliceModel {
		return $this->where('farm', ...$data);
	}

	public function whereSequence(...$data): SliceModel {
		return $this->where('sequence', ...$data);
	}

	public function whereCrop(...$data): SliceModel {
		return $this->where('crop', ...$data);
	}

	public function wherePlant(...$data): SliceModel {
		return $this->where('plant', ...$data);
	}

	public function whereVariety(...$data): SliceModel {
		return $this->where('variety', ...$data);
	}

	public function wherePartPercent(...$data): SliceModel {
		return $this->where('partPercent', ...$data);
	}


}


abstract class SliceCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Slice {

		$e = new Slice();

		if(empty($id)) {
			Slice::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Slice::getSelection();
		}

		if(Slice::model()
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
			$properties = Slice::getSelection();
		}

		if($sort !== NULL) {
			Slice::model()->sort($sort);
		}

		return Slice::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Slice {

		return new Slice(['id' => NULL]);

	}

	public static function create(Slice $e): void {

		Slice::model()->insert($e);

	}

	public static function update(Slice $e, array $properties): void {

		$e->expects(['id']);

		Slice::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Slice $e, array $properties): void {

		Slice::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Slice $e): void {

		$e->expects(['id']);

		Slice::model()->delete($e);

	}

}


class SlicePage extends \ModulePage {

	protected string $module = 'production\Slice';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SliceLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SliceLib::getPropertiesUpdate()
		);
	}

}
?>