<?php
namespace production;

abstract class CropElement extends \Element {

	use \FilterElement;

	private static ?CropModel $model = NULL;

	const SOWING = 'sowing';
	const PLANTING = 'planting';

	const SPACING = 'spacing';
	const DENSITY = 'density';

	const KG = 'kg';
	const UNIT = 'unit';
	const BUNCH = 'bunch';

	const YOUNG_PLANT = 'young-plant';

	public static function getSelection(): array {
		return Crop::model()->getProperties();
	}

	public static function model(): CropModel {
		if(self::$model === NULL) {
			self::$model = new CropModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Crop::'.$failName, $arguments, $wrapper);
	}

}


class CropModel extends \ModuleModel {

	protected string $module = 'production\Crop';
	protected string $package = 'production';
	protected string $table = 'productionCrop';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'plant' => ['element32', 'plant\Plant', 'cast' => 'element'],
			'startWeek' => ['int16', 'null' => TRUE, 'cast' => 'int'],
			'startAction' => ['enum', [\production\Crop::SOWING, \production\Crop::PLANTING], 'null' => TRUE, 'cast' => 'enum'],
			'sequence' => ['element32', 'production\Sequence', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'distance' => ['enum', [\production\Crop::SPACING, \production\Crop::DENSITY], 'cast' => 'enum'],
			'rows' => ['int8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'rowSpacing' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'plantSpacing' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'density' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'mainUnit' => ['enum', [\production\Crop::KG, \production\Crop::UNIT, \production\Crop::BUNCH], 'cast' => 'enum'],
			'seedling' => ['enum', [\production\Crop::SOWING, \production\Crop::YOUNG_PLANT], 'null' => TRUE, 'cast' => 'enum'],
			'seedlingSeeds' => ['int8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'yieldExpected' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'plant', 'startWeek', 'startAction', 'sequence', 'farm', 'distance', 'rows', 'rowSpacing', 'plantSpacing', 'density', 'mainUnit', 'seedling', 'seedlingSeeds', 'yieldExpected'
		]);

		$this->propertiesToModule += [
			'plant' => 'plant\Plant',
			'sequence' => 'production\Sequence',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['plant', 'farm']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['sequence', 'plant']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'distance' :
				return Crop::SPACING;

			case 'mainUnit' :
				return Crop::KG;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'startAction' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'distance' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'mainUnit' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'seedling' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): CropModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CropModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CropModel {
		return $this->where('id', ...$data);
	}

	public function wherePlant(...$data): CropModel {
		return $this->where('plant', ...$data);
	}

	public function whereStartWeek(...$data): CropModel {
		return $this->where('startWeek', ...$data);
	}

	public function whereStartAction(...$data): CropModel {
		return $this->where('startAction', ...$data);
	}

	public function whereSequence(...$data): CropModel {
		return $this->where('sequence', ...$data);
	}

	public function whereFarm(...$data): CropModel {
		return $this->where('farm', ...$data);
	}

	public function whereDistance(...$data): CropModel {
		return $this->where('distance', ...$data);
	}

	public function whereRows(...$data): CropModel {
		return $this->where('rows', ...$data);
	}

	public function whereRowSpacing(...$data): CropModel {
		return $this->where('rowSpacing', ...$data);
	}

	public function wherePlantSpacing(...$data): CropModel {
		return $this->where('plantSpacing', ...$data);
	}

	public function whereDensity(...$data): CropModel {
		return $this->where('density', ...$data);
	}

	public function whereMainUnit(...$data): CropModel {
		return $this->where('mainUnit', ...$data);
	}

	public function whereSeedling(...$data): CropModel {
		return $this->where('seedling', ...$data);
	}

	public function whereSeedlingSeeds(...$data): CropModel {
		return $this->where('seedlingSeeds', ...$data);
	}

	public function whereYieldExpected(...$data): CropModel {
		return $this->where('yieldExpected', ...$data);
	}


}


abstract class CropCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Crop {

		$e = new Crop();

		if(empty($id)) {
			Crop::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Crop::getSelection();
		}

		if(Crop::model()
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
			$properties = Crop::getSelection();
		}

		if($sort !== NULL) {
			Crop::model()->sort($sort);
		}

		return Crop::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Crop {

		return new Crop(['id' => NULL]);

	}

	public static function create(Crop $e): void {

		Crop::model()->insert($e);

	}

	public static function update(Crop $e, array $properties): void {

		$e->expects(['id']);

		Crop::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Crop $e, array $properties): void {

		Crop::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Crop $e): void {

		$e->expects(['id']);

		Crop::model()->delete($e);

	}

}


class CropPage extends \ModulePage {

	protected string $module = 'production\Crop';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CropLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CropLib::getPropertiesUpdate()
		);
	}

}
?>