<?php
namespace series;

abstract class CultivationElement extends \Element {

	use \FilterElement;

	private static ?CultivationModel $model = NULL;

	const SOWING = 'sowing';
	const PLANTING = 'planting';

	const SPACING = 'spacing';
	const DENSITY = 'density';

	const PERCENT = 'percent';
	const AREA = 'area';
	const LENGTH = 'length';

	const YOUNG_PLANT = 'young-plant';
	const YOUNG_PLANT_BOUGHT = 'young-plant-bought';

	const KG = 'kg';
	const UNIT = 'unit';
	const BUNCH = 'bunch';

	const WEEK = 'week';
	const MONTH = 'month';

	public static function getSelection(): array {
		return Cultivation::model()->getProperties();
	}

	public static function model(): CultivationModel {
		if(self::$model === NULL) {
			self::$model = new CultivationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Cultivation::'.$failName, $arguments, $wrapper);
	}

}


class CultivationModel extends \ModuleModel {

	protected string $module = 'series\Cultivation';
	protected string $package = 'series';
	protected string $table = 'seriesCultivation';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'series' => ['element32', 'series\Series', 'cast' => 'element'],
			'sequence' => ['element32', 'production\Sequence', 'null' => TRUE, 'cast' => 'element'],
			'crop' => ['element32', 'production\Crop', 'null' => TRUE, 'cast' => 'element'],
			'plant' => ['element32', 'plant\Plant', 'cast' => 'element'],
			'startWeek' => ['int16', 'null' => TRUE, 'cast' => 'int'],
			'startAction' => ['enum', [\series\Cultivation::SOWING, \series\Cultivation::PLANTING], 'null' => TRUE, 'cast' => 'enum'],
			'distance' => ['enum', [\series\Cultivation::SPACING, \series\Cultivation::DENSITY], 'cast' => 'enum'],
			'rows' => ['int8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'rowSpacing' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'plantSpacing' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'density' => ['float32', 'min' => 0.01, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'sliceUnit' => ['enum', [\series\Cultivation::PERCENT, \series\Cultivation::AREA, \series\Cultivation::LENGTH], 'cast' => 'enum'],
			'area' => ['int24', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'areaPermanent' => ['int24', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'length' => ['int16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'lengthPermanent' => ['int24', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'seedling' => ['enum', [\series\Cultivation::SOWING, \series\Cultivation::YOUNG_PLANT, \series\Cultivation::YOUNG_PLANT_BOUGHT], 'null' => TRUE, 'cast' => 'enum'],
			'seedlingSeeds' => ['int8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'harvested' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'harvestedNormalized' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'harvestedByUnit' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'mainUnit' => ['enum', [\series\Cultivation::KG, \series\Cultivation::UNIT, \series\Cultivation::BUNCH], 'cast' => 'enum'],
			'unitWeight' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'bunchWeight' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'yieldExpected' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'harvestMonths' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'harvestWeeks' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'harvestPeriodExpected' => ['enum', [\series\Cultivation::WEEK, \series\Cultivation::MONTH], 'cast' => 'enum'],
			'harvestMonthsExpected' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'harvestWeeksExpected' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'season', 'series', 'sequence', 'crop', 'plant', 'startWeek', 'startAction', 'distance', 'rows', 'rowSpacing', 'plantSpacing', 'density', 'sliceUnit', 'area', 'areaPermanent', 'length', 'lengthPermanent', 'seedling', 'seedlingSeeds', 'harvested', 'harvestedNormalized', 'harvestedByUnit', 'mainUnit', 'unitWeight', 'bunchWeight', 'yieldExpected', 'harvestMonths', 'harvestWeeks', 'harvestPeriodExpected', 'harvestMonthsExpected', 'harvestWeeksExpected', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'series' => 'series\Series',
			'sequence' => 'production\Sequence',
			'crop' => 'production\Crop',
			'plant' => 'plant\Plant',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'season'],
			['farm', 'plant'],
			['sequence']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['series', 'plant']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'distance' :
				return Cultivation::SPACING;

			case 'sliceUnit' :
				return Cultivation::PERCENT;

			case 'mainUnit' :
				return Cultivation::KG;

			case 'harvestPeriodExpected' :
				return Cultivation::MONTH;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

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

			case 'sliceUnit' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'seedling' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'harvestedByUnit' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'mainUnit' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'harvestMonths' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'harvestWeeks' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'harvestPeriodExpected' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'harvestMonthsExpected' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'harvestWeeksExpected' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'harvestedByUnit' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'harvestMonths' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'harvestWeeks' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'harvestMonthsExpected' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'harvestWeeksExpected' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): CultivationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CultivationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CultivationModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): CultivationModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeason(...$data): CultivationModel {
		return $this->where('season', ...$data);
	}

	public function whereSeries(...$data): CultivationModel {
		return $this->where('series', ...$data);
	}

	public function whereSequence(...$data): CultivationModel {
		return $this->where('sequence', ...$data);
	}

	public function whereCrop(...$data): CultivationModel {
		return $this->where('crop', ...$data);
	}

	public function wherePlant(...$data): CultivationModel {
		return $this->where('plant', ...$data);
	}

	public function whereStartWeek(...$data): CultivationModel {
		return $this->where('startWeek', ...$data);
	}

	public function whereStartAction(...$data): CultivationModel {
		return $this->where('startAction', ...$data);
	}

	public function whereDistance(...$data): CultivationModel {
		return $this->where('distance', ...$data);
	}

	public function whereRows(...$data): CultivationModel {
		return $this->where('rows', ...$data);
	}

	public function whereRowSpacing(...$data): CultivationModel {
		return $this->where('rowSpacing', ...$data);
	}

	public function wherePlantSpacing(...$data): CultivationModel {
		return $this->where('plantSpacing', ...$data);
	}

	public function whereDensity(...$data): CultivationModel {
		return $this->where('density', ...$data);
	}

	public function whereSliceUnit(...$data): CultivationModel {
		return $this->where('sliceUnit', ...$data);
	}

	public function whereArea(...$data): CultivationModel {
		return $this->where('area', ...$data);
	}

	public function whereAreaPermanent(...$data): CultivationModel {
		return $this->where('areaPermanent', ...$data);
	}

	public function whereLength(...$data): CultivationModel {
		return $this->where('length', ...$data);
	}

	public function whereLengthPermanent(...$data): CultivationModel {
		return $this->where('lengthPermanent', ...$data);
	}

	public function whereSeedling(...$data): CultivationModel {
		return $this->where('seedling', ...$data);
	}

	public function whereSeedlingSeeds(...$data): CultivationModel {
		return $this->where('seedlingSeeds', ...$data);
	}

	public function whereHarvested(...$data): CultivationModel {
		return $this->where('harvested', ...$data);
	}

	public function whereHarvestedNormalized(...$data): CultivationModel {
		return $this->where('harvestedNormalized', ...$data);
	}

	public function whereHarvestedByUnit(...$data): CultivationModel {
		return $this->where('harvestedByUnit', ...$data);
	}

	public function whereMainUnit(...$data): CultivationModel {
		return $this->where('mainUnit', ...$data);
	}

	public function whereUnitWeight(...$data): CultivationModel {
		return $this->where('unitWeight', ...$data);
	}

	public function whereBunchWeight(...$data): CultivationModel {
		return $this->where('bunchWeight', ...$data);
	}

	public function whereYieldExpected(...$data): CultivationModel {
		return $this->where('yieldExpected', ...$data);
	}

	public function whereHarvestMonths(...$data): CultivationModel {
		return $this->where('harvestMonths', ...$data);
	}

	public function whereHarvestWeeks(...$data): CultivationModel {
		return $this->where('harvestWeeks', ...$data);
	}

	public function whereHarvestPeriodExpected(...$data): CultivationModel {
		return $this->where('harvestPeriodExpected', ...$data);
	}

	public function whereHarvestMonthsExpected(...$data): CultivationModel {
		return $this->where('harvestMonthsExpected', ...$data);
	}

	public function whereHarvestWeeksExpected(...$data): CultivationModel {
		return $this->where('harvestWeeksExpected', ...$data);
	}

	public function whereCreatedAt(...$data): CultivationModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): CultivationModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class CultivationCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Cultivation {

		$e = new Cultivation();

		if(empty($id)) {
			Cultivation::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Cultivation::getSelection();
		}

		if(Cultivation::model()
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
			$properties = Cultivation::getSelection();
		}

		if($sort !== NULL) {
			Cultivation::model()->sort($sort);
		}

		return Cultivation::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Cultivation {

		return new Cultivation(['id' => NULL]);

	}

	public static function create(Cultivation $e): void {

		Cultivation::model()->insert($e);

	}

	public static function update(Cultivation $e, array $properties): void {

		$e->expects(['id']);

		Cultivation::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Cultivation $e, array $properties): void {

		Cultivation::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Cultivation $e): void {

		$e->expects(['id']);

		Cultivation::model()->delete($e);

	}

}


class CultivationPage extends \ModulePage {

	protected string $module = 'series\Cultivation';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CultivationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CultivationLib::getPropertiesUpdate()
		);
	}

}
?>