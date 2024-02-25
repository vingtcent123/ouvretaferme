<?php
namespace farm;

abstract class ToolElement extends \Element {

	use \FilterElement;

	private static ?ToolModel $model = NULL;

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

	public static function getSelection(): array {
		return Tool::model()->getProperties();
	}

	public static function model(): ToolModel {
		if(self::$model === NULL) {
			self::$model = new ToolModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Tool::'.$failName, $arguments, $wrapper);
	}

}


class ToolModel extends \ModuleModel {

	protected string $module = 'farm\Tool';
	protected string $package = 'farm';
	protected string $table = 'farmTool';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 40, 'collate' => 'general', 'cast' => 'string'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'action' => ['element32', 'farm\Action', 'null' => TRUE, 'cast' => 'element'],
			'vignette' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'stock' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'routineName' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'routineValue' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'comment' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\farm\Tool::ACTIVE, \farm\Tool::INACTIVE], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'farm', 'action', 'vignette', 'stock', 'routineName', 'routineValue', 'comment', 'status', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'action' => 'farm\Action',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'name']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Tool::ACTIVE;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'routineValue' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'routineValue' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): ToolModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ToolModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ToolModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): ToolModel {
		return $this->where('name', ...$data);
	}

	public function whereFarm(...$data): ToolModel {
		return $this->where('farm', ...$data);
	}

	public function whereAction(...$data): ToolModel {
		return $this->where('action', ...$data);
	}

	public function whereVignette(...$data): ToolModel {
		return $this->where('vignette', ...$data);
	}

	public function whereStock(...$data): ToolModel {
		return $this->where('stock', ...$data);
	}

	public function whereRoutineName(...$data): ToolModel {
		return $this->where('routineName', ...$data);
	}

	public function whereRoutineValue(...$data): ToolModel {
		return $this->where('routineValue', ...$data);
	}

	public function whereComment(...$data): ToolModel {
		return $this->where('comment', ...$data);
	}

	public function whereStatus(...$data): ToolModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): ToolModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class ToolCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Tool {

		$e = new Tool();

		if(empty($id)) {
			Tool::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Tool::getSelection();
		}

		if(Tool::model()
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
			$properties = Tool::getSelection();
		}

		if($sort !== NULL) {
			Tool::model()->sort($sort);
		}

		return Tool::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Tool {

		return new Tool(['id' => NULL]);

	}

	public static function create(Tool $e): void {

		Tool::model()->insert($e);

	}

	public static function update(Tool $e, array $properties): void {

		$e->expects(['id']);

		Tool::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Tool $e, array $properties): void {

		Tool::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Tool $e): void {

		$e->expects(['id']);

		Tool::model()->delete($e);

	}

}


class ToolPage extends \ModulePage {

	protected string $module = 'farm\Tool';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ToolLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ToolLib::getPropertiesUpdate()
		);
	}

}
?>