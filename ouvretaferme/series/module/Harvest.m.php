<?php
namespace series;

abstract class HarvestElement extends \Element {

	use \FilterElement;

	private static ?HarvestModel $model = NULL;

	const KG = 'kg';
	const UNIT = 'unit';
	const BUNCH = 'bunch';

	public static function getSelection(): array {
		return Harvest::model()->getProperties();
	}

	public static function model(): HarvestModel {
		if(self::$model === NULL) {
			self::$model = new HarvestModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Harvest::'.$failName, $arguments, $wrapper);
	}

}


class HarvestModel extends \ModuleModel {

	protected string $module = 'series\Harvest';
	protected string $package = 'series';
	protected string $table = 'seriesHarvest';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'series' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'cultivation' => ['element32', 'series\Cultivation', 'null' => TRUE, 'cast' => 'element'],
			'task' => ['element32', 'series\Task', 'cast' => 'element'],
			'quantity' => ['float32', 'cast' => 'float'],
			'unit' => ['enum', [\series\Harvest::KG, \series\Harvest::UNIT, \series\Harvest::BUNCH], 'cast' => 'enum'],
			'date' => ['date', 'cast' => 'string'],
			'week' => ['week', 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'series', 'cultivation', 'task', 'quantity', 'unit', 'date', 'week', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'series' => 'series\Series',
			'cultivation' => 'series\Cultivation',
			'task' => 'series\Task',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'date'],
			['farm', 'week'],
			['task', 'date']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

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

			case 'unit' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): HarvestModel {
		return parent::select(...$fields);
	}

	public function where(...$data): HarvestModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): HarvestModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): HarvestModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeries(...$data): HarvestModel {
		return $this->where('series', ...$data);
	}

	public function whereCultivation(...$data): HarvestModel {
		return $this->where('cultivation', ...$data);
	}

	public function whereTask(...$data): HarvestModel {
		return $this->where('task', ...$data);
	}

	public function whereQuantity(...$data): HarvestModel {
		return $this->where('quantity', ...$data);
	}

	public function whereUnit(...$data): HarvestModel {
		return $this->where('unit', ...$data);
	}

	public function whereDate(...$data): HarvestModel {
		return $this->where('date', ...$data);
	}

	public function whereWeek(...$data): HarvestModel {
		return $this->where('week', ...$data);
	}

	public function whereCreatedAt(...$data): HarvestModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): HarvestModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class HarvestCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Harvest {

		$e = new Harvest();

		if(empty($id)) {
			Harvest::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Harvest::getSelection();
		}

		if(Harvest::model()
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
			$properties = Harvest::getSelection();
		}

		if($sort !== NULL) {
			Harvest::model()->sort($sort);
		}

		return Harvest::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Harvest {

		return new Harvest(['id' => NULL]);

	}

	public static function create(Harvest $e): void {

		Harvest::model()->insert($e);

	}

	public static function update(Harvest $e, array $properties): void {

		$e->expects(['id']);

		Harvest::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Harvest $e, array $properties): void {

		Harvest::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Harvest $e): void {

		$e->expects(['id']);

		Harvest::model()->delete($e);

	}

}


class HarvestPage extends \ModulePage {

	protected string $module = 'series\Harvest';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? HarvestLib::getPropertiesCreate(),
		   $propertiesUpdate ?? HarvestLib::getPropertiesUpdate()
		);
	}

}
?>