<?php
namespace dev;

abstract class ErrorTraceElement extends \Element {

	use \FilterElement;

	private static ?ErrorTraceModel $model = NULL;

	public static function getSelection(): array {
		return ErrorTrace::model()->getProperties();
	}

	public static function model(): ErrorTraceModel {
		if(self::$model === NULL) {
			self::$model = new ErrorTraceModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('ErrorTrace::'.$failName, $arguments, $wrapper);
	}

}


class ErrorTraceModel extends \ModuleModel {

	protected string $module = 'dev\ErrorTrace';
	protected string $package = 'dev';
	protected string $table = 'devErrorTrace';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'error' => ['element32', 'dev\Error', 'cast' => 'element'],
			'file' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'line' => ['int32', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'class' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'function' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'arguments' => ['text16', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'error', 'file', 'line', 'class', 'function', 'arguments'
		]);

		$this->propertiesToModule += [
			'error' => 'dev\Error',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['error']
		]);

	}

	public function select(...$fields): ErrorTraceModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ErrorTraceModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ErrorTraceModel {
		return $this->where('id', ...$data);
	}

	public function whereError(...$data): ErrorTraceModel {
		return $this->where('error', ...$data);
	}

	public function whereFile(...$data): ErrorTraceModel {
		return $this->where('file', ...$data);
	}

	public function whereLine(...$data): ErrorTraceModel {
		return $this->where('line', ...$data);
	}

	public function whereClass(...$data): ErrorTraceModel {
		return $this->where('class', ...$data);
	}

	public function whereFunction(...$data): ErrorTraceModel {
		return $this->where('function', ...$data);
	}

	public function whereArguments(...$data): ErrorTraceModel {
		return $this->where('arguments', ...$data);
	}


}


abstract class ErrorTraceCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): ErrorTrace {

		$e = new ErrorTrace();

		if(empty($id)) {
			ErrorTrace::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = ErrorTrace::getSelection();
		}

		if(ErrorTrace::model()
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
			$properties = ErrorTrace::getSelection();
		}

		if($sort !== NULL) {
			ErrorTrace::model()->sort($sort);
		}

		return ErrorTrace::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): ErrorTrace {

		return new ErrorTrace(['id' => NULL]);

	}

	public static function create(ErrorTrace $e): void {

		ErrorTrace::model()->insert($e);

	}

	public static function update(ErrorTrace $e, array $properties): void {

		$e->expects(['id']);

		ErrorTrace::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, ErrorTrace $e, array $properties): void {

		ErrorTrace::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(ErrorTrace $e): void {

		$e->expects(['id']);

		ErrorTrace::model()->delete($e);

	}

}


class ErrorTracePage extends \ModulePage {

	protected string $module = 'dev\ErrorTrace';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ErrorTraceLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ErrorTraceLib::getPropertiesUpdate()
		);
	}

}
?>