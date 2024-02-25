<?php
namespace farm;

abstract class ActionElement extends \Element {

	use \FilterElement;

	private static ?ActionModel $model = NULL;

	const BY_HARVEST = 'by-harvest';
	const BY_AREA = 'by-area';
	const BY_PLANT = 'by-plant';

	public static function getSelection(): array {
		return Action::model()->getProperties();
	}

	public static function model(): ActionModel {
		if(self::$model === NULL) {
			self::$model = new ActionModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Action::'.$failName, $arguments, $wrapper);
	}

}


class ActionModel extends \ModuleModel {

	protected string $module = 'farm\Action';
	protected string $package = 'farm';
	protected string $table = 'farmAction';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'fqn' => ['text8', 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'short' => ['text8', 'min' => 1, 'max' => 2, 'null' => TRUE, 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'null' => TRUE, 'cast' => 'element'],
			'color' => ['color', 'cast' => 'string'],
			'pace' => ['enum', [\farm\Action::BY_HARVEST, \farm\Action::BY_AREA, \farm\Action::BY_PLANT], 'null' => TRUE, 'cast' => 'enum'],
			'categories' => ['json', 'cast' => 'array'],
			'series' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'fqn', 'short', 'farm', 'color', 'pace', 'categories', 'series'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'color' :
				return '#AAAAAA';

			case 'categories' :
				return [];

			case 'series' :
				return TRUE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'pace' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'categories' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'categories' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): ActionModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ActionModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ActionModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): ActionModel {
		return $this->where('name', ...$data);
	}

	public function whereFqn(...$data): ActionModel {
		return $this->where('fqn', ...$data);
	}

	public function whereShort(...$data): ActionModel {
		return $this->where('short', ...$data);
	}

	public function whereFarm(...$data): ActionModel {
		return $this->where('farm', ...$data);
	}

	public function whereColor(...$data): ActionModel {
		return $this->where('color', ...$data);
	}

	public function wherePace(...$data): ActionModel {
		return $this->where('pace', ...$data);
	}

	public function whereCategories(...$data): ActionModel {
		return $this->where('categories', ...$data);
	}

	public function whereSeries(...$data): ActionModel {
		return $this->where('series', ...$data);
	}


}


abstract class ActionCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Action {

		$e = new Action();

		if(empty($id)) {
			Action::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Action::getSelection();
		}

		if(Action::model()
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
			$properties = Action::getSelection();
		}

		if($sort !== NULL) {
			Action::model()->sort($sort);
		}

		return Action::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Action {

		return new Action(['id' => NULL]);

	}

	public static function create(Action $e): void {

		Action::model()->insert($e);

	}

	public static function update(Action $e, array $properties): void {

		$e->expects(['id']);

		Action::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Action $e, array $properties): void {

		Action::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Action $e): void {

		$e->expects(['id']);

		Action::model()->delete($e);

	}

}


class ActionPage extends \ModulePage {

	protected string $module = 'farm\Action';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ActionLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ActionLib::getPropertiesUpdate()
		);
	}

}
?>