<?php
namespace series;

abstract class RepeatElement extends \Element {

	use \FilterElement;

	private static ?RepeatModel $model = NULL;

	const TODO = 'todo';
	const DONE = 'done';

	const W1 = 'w1';
	const W2 = 'w2';
	const W3 = 'w3';
	const W4 = 'w4';
	const M1 = 'm1';

	public static function getSelection(): array {
		return Repeat::model()->getProperties();
	}

	public static function model(): RepeatModel {
		if(self::$model === NULL) {
			self::$model = new RepeatModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Repeat::'.$failName, $arguments, $wrapper);
	}

}


class RepeatModel extends \ModuleModel {

	protected string $module = 'series\Repeat';
	protected string $package = 'series';
	protected string $table = 'seriesRepeat';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'cultivation' => ['element32', 'series\Cultivation', 'null' => TRUE, 'cast' => 'element'],
			'series' => ['element32', 'series\Series', 'null' => TRUE, 'cast' => 'element'],
			'plant' => ['element32', 'plant\Plant', 'null' => TRUE, 'cast' => 'element'],
			'variety' => ['element32', 'plant\Variety', 'null' => TRUE, 'cast' => 'element'],
			'action' => ['element32', 'farm\Action', 'cast' => 'element'],
			'category' => ['element32', 'farm\Category', 'cast' => 'element'],
			'description' => ['text16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'timeExpected' => ['float32', 'min' => 0.0, 'max' => NULL, 'null' => TRUE, 'cast' => 'float'],
			'fertilizer' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'tools' => ['json', 'cast' => 'array'],
			'status' => ['enum', [\series\Repeat::TODO, \series\Repeat::DONE], 'cast' => 'enum'],
			'frequency' => ['enum', [\series\Repeat::W1, \series\Repeat::W2, \series\Repeat::W3, \series\Repeat::W4, \series\Repeat::M1], 'cast' => 'enum'],
			'start' => ['date', 'cast' => 'string'],
			'current' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'discrete' => ['json', 'cast' => 'array'],
			'stop' => ['week', 'null' => TRUE, 'cast' => 'string'],
			'completed' => ['bool', 'cast' => 'bool'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'season', 'cultivation', 'series', 'plant', 'variety', 'action', 'category', 'description', 'timeExpected', 'fertilizer', 'tools', 'status', 'frequency', 'start', 'current', 'discrete', 'stop', 'completed', 'createdBy', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'cultivation' => 'series\Cultivation',
			'series' => 'series\Series',
			'plant' => 'plant\Plant',
			'variety' => 'plant\Variety',
			'action' => 'farm\Action',
			'category' => 'farm\Category',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'current']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'discrete' :
				return [];

			case 'completed' :
				return FALSE;

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'fertilizer' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'tools' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'frequency' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'discrete' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'fertilizer' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'tools' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'discrete' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): RepeatModel {
		return parent::select(...$fields);
	}

	public function where(...$data): RepeatModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): RepeatModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): RepeatModel {
		return $this->where('farm', ...$data);
	}

	public function whereSeason(...$data): RepeatModel {
		return $this->where('season', ...$data);
	}

	public function whereCultivation(...$data): RepeatModel {
		return $this->where('cultivation', ...$data);
	}

	public function whereSeries(...$data): RepeatModel {
		return $this->where('series', ...$data);
	}

	public function wherePlant(...$data): RepeatModel {
		return $this->where('plant', ...$data);
	}

	public function whereVariety(...$data): RepeatModel {
		return $this->where('variety', ...$data);
	}

	public function whereAction(...$data): RepeatModel {
		return $this->where('action', ...$data);
	}

	public function whereCategory(...$data): RepeatModel {
		return $this->where('category', ...$data);
	}

	public function whereDescription(...$data): RepeatModel {
		return $this->where('description', ...$data);
	}

	public function whereTimeExpected(...$data): RepeatModel {
		return $this->where('timeExpected', ...$data);
	}

	public function whereFertilizer(...$data): RepeatModel {
		return $this->where('fertilizer', ...$data);
	}

	public function whereTools(...$data): RepeatModel {
		return $this->where('tools', ...$data);
	}

	public function whereStatus(...$data): RepeatModel {
		return $this->where('status', ...$data);
	}

	public function whereFrequency(...$data): RepeatModel {
		return $this->where('frequency', ...$data);
	}

	public function whereStart(...$data): RepeatModel {
		return $this->where('start', ...$data);
	}

	public function whereCurrent(...$data): RepeatModel {
		return $this->where('current', ...$data);
	}

	public function whereDiscrete(...$data): RepeatModel {
		return $this->where('discrete', ...$data);
	}

	public function whereStop(...$data): RepeatModel {
		return $this->where('stop', ...$data);
	}

	public function whereCompleted(...$data): RepeatModel {
		return $this->where('completed', ...$data);
	}

	public function whereCreatedBy(...$data): RepeatModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereCreatedAt(...$data): RepeatModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class RepeatCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Repeat {

		$e = new Repeat();

		if(empty($id)) {
			Repeat::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Repeat::getSelection();
		}

		if(Repeat::model()
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
			$properties = Repeat::getSelection();
		}

		if($sort !== NULL) {
			Repeat::model()->sort($sort);
		}

		return Repeat::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Repeat {

		return new Repeat(['id' => NULL]);

	}

	public static function create(Repeat $e): void {

		Repeat::model()->insert($e);

	}

	public static function update(Repeat $e, array $properties): void {

		$e->expects(['id']);

		Repeat::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Repeat $e, array $properties): void {

		Repeat::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Repeat $e): void {

		$e->expects(['id']);

		Repeat::model()->delete($e);

	}

}


class RepeatPage extends \ModulePage {

	protected string $module = 'series\Repeat';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? RepeatLib::getPropertiesCreate(),
		   $propertiesUpdate ?? RepeatLib::getPropertiesUpdate()
		);
	}

}
?>