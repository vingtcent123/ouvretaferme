<?php
namespace production;

abstract class FlowElement extends \Element {

	use \FilterElement;

	private static ?FlowModel $model = NULL;

	const W1 = 'w1';
	const W2 = 'w2';
	const W3 = 'w3';
	const W4 = 'w4';
	const M1 = 'm1';

	public static function getSelection(): array {
		return Flow::model()->getProperties();
	}

	public static function model(): FlowModel {
		if(self::$model === NULL) {
			self::$model = new FlowModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Flow::'.$failName, $arguments, $wrapper);
	}

}


class FlowModel extends \ModuleModel {

	protected string $module = 'production\Flow';
	protected string $package = 'production';
	protected string $table = 'productionFlow';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'crop' => ['element32', 'production\Crop', 'null' => TRUE, 'cast' => 'element'],
			'plant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'sequence' => ['element32', 'production\Sequence', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'action' => ['element32', 'farm\Action', 'cast' => 'element'],
			'description' => ['text16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'fertilizer' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'weekOnly' => ['int8', 'min' => 1, 'max' => 52, 'null' => TRUE, 'cast' => 'int'],
			'weekStart' => ['int8', 'min' => 1, 'max' => 52, 'null' => TRUE, 'cast' => 'int'],
			'weekStop' => ['int8', 'min' => 1, 'max' => 52, 'null' => TRUE, 'cast' => 'int'],
			'yearOnly' => ['int8', 'min' => -1, 'max' => 1, 'null' => TRUE, 'cast' => 'int'],
			'yearStart' => ['int8', 'min' => -1, 'max' => 1, 'null' => TRUE, 'cast' => 'int'],
			'yearStop' => ['int8', 'min' => -1, 'max' => 1, 'null' => TRUE, 'cast' => 'int'],
			'seasonOnly' => ['int8', 'min' => 1, 'max' => 100, 'null' => TRUE, 'cast' => 'int'],
			'seasonStart' => ['int8', 'min' => 1, 'max' => 100, 'null' => TRUE, 'cast' => 'int'],
			'seasonStop' => ['int8', 'min' => 1, 'max' => 100, 'null' => TRUE, 'cast' => 'int'],
			'positionOnly' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'positionStart' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'positionStop' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'frequency' => ['enum', [\production\Flow::W1, \production\Flow::W2, \production\Flow::W3, \production\Flow::W4, \production\Flow::M1], 'null' => TRUE, 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'crop', 'plant', 'sequence', 'farm', 'action', 'description', 'fertilizer', 'weekOnly', 'weekStart', 'weekStop', 'yearOnly', 'yearStart', 'yearStop', 'seasonOnly', 'seasonStart', 'seasonStop', 'positionOnly', 'positionStart', 'positionStop', 'frequency'
		]);

		$this->propertiesToModule += [
			'crop' => 'production\Crop',
			'plant' => 'plant\Plant',
			'sequence' => 'production\Sequence',
			'farm' => 'farm\Farm',
			'action' => 'farm\Action',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['plant'],
			['crop'],
			['sequence']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'fertilizer' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'frequency' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'fertilizer' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): FlowModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FlowModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): FlowModel {
		return $this->where('id', ...$data);
	}

	public function whereCrop(...$data): FlowModel {
		return $this->where('crop', ...$data);
	}

	public function wherePlant(...$data): FlowModel {
		return $this->where('plant', ...$data);
	}

	public function whereSequence(...$data): FlowModel {
		return $this->where('sequence', ...$data);
	}

	public function whereFarm(...$data): FlowModel {
		return $this->where('farm', ...$data);
	}

	public function whereAction(...$data): FlowModel {
		return $this->where('action', ...$data);
	}

	public function whereDescription(...$data): FlowModel {
		return $this->where('description', ...$data);
	}

	public function whereFertilizer(...$data): FlowModel {
		return $this->where('fertilizer', ...$data);
	}

	public function whereWeekOnly(...$data): FlowModel {
		return $this->where('weekOnly', ...$data);
	}

	public function whereWeekStart(...$data): FlowModel {
		return $this->where('weekStart', ...$data);
	}

	public function whereWeekStop(...$data): FlowModel {
		return $this->where('weekStop', ...$data);
	}

	public function whereYearOnly(...$data): FlowModel {
		return $this->where('yearOnly', ...$data);
	}

	public function whereYearStart(...$data): FlowModel {
		return $this->where('yearStart', ...$data);
	}

	public function whereYearStop(...$data): FlowModel {
		return $this->where('yearStop', ...$data);
	}

	public function whereSeasonOnly(...$data): FlowModel {
		return $this->where('seasonOnly', ...$data);
	}

	public function whereSeasonStart(...$data): FlowModel {
		return $this->where('seasonStart', ...$data);
	}

	public function whereSeasonStop(...$data): FlowModel {
		return $this->where('seasonStop', ...$data);
	}

	public function wherePositionOnly(...$data): FlowModel {
		return $this->where('positionOnly', ...$data);
	}

	public function wherePositionStart(...$data): FlowModel {
		return $this->where('positionStart', ...$data);
	}

	public function wherePositionStop(...$data): FlowModel {
		return $this->where('positionStop', ...$data);
	}

	public function whereFrequency(...$data): FlowModel {
		return $this->where('frequency', ...$data);
	}


}


abstract class FlowCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Flow {

		$e = new Flow();

		if(empty($id)) {
			Flow::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Flow::getSelection();
		}

		if(Flow::model()
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
			$properties = Flow::getSelection();
		}

		if($sort !== NULL) {
			Flow::model()->sort($sort);
		}

		return Flow::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Flow {

		return new Flow(['id' => NULL]);

	}

	public static function create(Flow $e): void {

		Flow::model()->insert($e);

	}

	public static function update(Flow $e, array $properties): void {

		$e->expects(['id']);

		Flow::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Flow $e, array $properties): void {

		Flow::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Flow $e): void {

		$e->expects(['id']);

		Flow::model()->delete($e);

	}

}


class FlowPage extends \ModulePage {

	protected string $module = 'production\Flow';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FlowLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FlowLib::getPropertiesUpdate()
		);
	}

}
?>