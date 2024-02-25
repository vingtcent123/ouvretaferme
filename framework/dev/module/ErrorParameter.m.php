<?php
namespace dev;

abstract class ErrorParameterElement extends \Element {

	use \FilterElement;

	private static ?ErrorParameterModel $model = NULL;

	const GET = 'get';
	const POST = 'post';
	const COOKIE = 'cookie';

	public static function getSelection(): array {
		return ErrorParameter::model()->getProperties();
	}

	public static function model(): ErrorParameterModel {
		if(self::$model === NULL) {
			self::$model = new ErrorParameterModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('ErrorParameter::'.$failName, $arguments, $wrapper);
	}

}


class ErrorParameterModel extends \ModuleModel {

	protected string $module = 'dev\ErrorParameter';
	protected string $package = 'dev';
	protected string $table = 'devErrorParameter';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'error' => ['element32', 'dev\Error', 'cast' => 'element'],
			'type' => ['enum', [\dev\ErrorParameter::GET, \dev\ErrorParameter::POST, \dev\ErrorParameter::COOKIE], 'cast' => 'enum'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'value' => ['text24', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'error', 'type', 'name', 'value'
		]);

		$this->propertiesToModule += [
			'error' => 'dev\Error',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['error']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): ErrorParameterModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ErrorParameterModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ErrorParameterModel {
		return $this->where('id', ...$data);
	}

	public function whereError(...$data): ErrorParameterModel {
		return $this->where('error', ...$data);
	}

	public function whereType(...$data): ErrorParameterModel {
		return $this->where('type', ...$data);
	}

	public function whereName(...$data): ErrorParameterModel {
		return $this->where('name', ...$data);
	}

	public function whereValue(...$data): ErrorParameterModel {
		return $this->where('value', ...$data);
	}


}


abstract class ErrorParameterCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): ErrorParameter {

		$e = new ErrorParameter();

		if(empty($id)) {
			ErrorParameter::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = ErrorParameter::getSelection();
		}

		if(ErrorParameter::model()
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
			$properties = ErrorParameter::getSelection();
		}

		if($sort !== NULL) {
			ErrorParameter::model()->sort($sort);
		}

		return ErrorParameter::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): ErrorParameter {

		return new ErrorParameter(['id' => NULL]);

	}

	public static function create(ErrorParameter $e): void {

		ErrorParameter::model()->insert($e);

	}

	public static function update(ErrorParameter $e, array $properties): void {

		$e->expects(['id']);

		ErrorParameter::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, ErrorParameter $e, array $properties): void {

		ErrorParameter::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(ErrorParameter $e): void {

		$e->expects(['id']);

		ErrorParameter::model()->delete($e);

	}

}


class ErrorParameterPage extends \ModulePage {

	protected string $module = 'dev\ErrorParameter';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ErrorParameterLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ErrorParameterLib::getPropertiesUpdate()
		);
	}

}
?>