<?php
namespace map;

abstract class DrawElement extends \Element {

	use \FilterElement;

	private static ?DrawModel $model = NULL;

	public static function getSelection(): array {
		return Draw::model()->getProperties();
	}

	public static function model(): DrawModel {
		if(self::$model === NULL) {
			self::$model = new DrawModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Draw::'.$failName, $arguments, $wrapper);
	}

}


class DrawModel extends \ModuleModel {

	protected string $module = 'map\Draw';
	protected string $package = 'map';
	protected string $table = 'mapDraw';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'zone' => ['element32', 'map\Zone', 'cast' => 'element'],
			'plot' => ['element32', 'map\Plot', 'cast' => 'element'],
			'season' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'beds' => ['json', 'cast' => 'array'],
			'coordinates' => ['json', 'cast' => 'array'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'zone', 'plot', 'season', 'beds', 'coordinates'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'zone' => 'map\Zone',
			'plot' => 'map\Plot',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['plot']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'beds' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'coordinates' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'beds' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'coordinates' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): DrawModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DrawModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DrawModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): DrawModel {
		return $this->where('farm', ...$data);
	}

	public function whereZone(...$data): DrawModel {
		return $this->where('zone', ...$data);
	}

	public function wherePlot(...$data): DrawModel {
		return $this->where('plot', ...$data);
	}

	public function whereSeason(...$data): DrawModel {
		return $this->where('season', ...$data);
	}

	public function whereBeds(...$data): DrawModel {
		return $this->where('beds', ...$data);
	}

	public function whereCoordinates(...$data): DrawModel {
		return $this->where('coordinates', ...$data);
	}


}


abstract class DrawCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Draw {

		$e = new Draw();

		if(empty($id)) {
			Draw::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Draw::getSelection();
		}

		if(Draw::model()
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
			$properties = Draw::getSelection();
		}

		if($sort !== NULL) {
			Draw::model()->sort($sort);
		}

		return Draw::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Draw {

		return new Draw(['id' => NULL]);

	}

	public static function create(Draw $e): void {

		Draw::model()->insert($e);

	}

	public static function update(Draw $e, array $properties): void {

		$e->expects(['id']);

		Draw::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Draw $e, array $properties): void {

		Draw::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Draw $e): void {

		$e->expects(['id']);

		Draw::model()->delete($e);

	}

}


class DrawPage extends \ModulePage {

	protected string $module = 'map\Draw';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DrawLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DrawLib::getPropertiesUpdate()
		);
	}

}
?>