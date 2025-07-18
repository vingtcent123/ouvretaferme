<?php
namespace asset;

abstract class RecognitionElement extends \Element {

	use \FilterElement;

	private static ?RecognitionModel $model = NULL;

	public static function getSelection(): array {
		return Recognition::model()->getProperties();
	}

	public static function model(): RecognitionModel {
		if(self::$model === NULL) {
			self::$model = new RecognitionModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Recognition::'.$failName, $arguments, $wrapper);
	}

}


class RecognitionModel extends \ModuleModel {

	protected string $module = 'asset\Recognition';
	protected string $package = 'asset';
	protected string $table = 'assetRecognition';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'grant' => ['element32', 'asset\Asset', 'cast' => 'element'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'date' => ['date', 'cast' => 'string'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'operation' => ['element32', 'journal\Operation', 'cast' => 'element'],
			'debitAccountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'creditAccountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'prorataDays' => ['float32', 'min' => 0.0, 'max' => 1.0, 'cast' => 'float'],
			'comment' => ['text24', 'min' => 1, 'max' => 'null) @collage(general', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'grant', 'financialYear', 'date', 'amount', 'operation', 'debitAccountLabel', 'creditAccountLabel', 'prorataDays', 'comment', 'createdAt'
		]);

		$this->propertiesToModule += [
			'grant' => 'asset\Asset',
			'financialYear' => 'account\FinancialYear',
			'operation' => 'journal\Operation',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): RecognitionModel {
		return parent::select(...$fields);
	}

	public function where(...$data): RecognitionModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): RecognitionModel {
		return $this->where('id', ...$data);
	}

	public function whereGrant(...$data): RecognitionModel {
		return $this->where('grant', ...$data);
	}

	public function whereFinancialYear(...$data): RecognitionModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereDate(...$data): RecognitionModel {
		return $this->where('date', ...$data);
	}

	public function whereAmount(...$data): RecognitionModel {
		return $this->where('amount', ...$data);
	}

	public function whereOperation(...$data): RecognitionModel {
		return $this->where('operation', ...$data);
	}

	public function whereDebitAccountLabel(...$data): RecognitionModel {
		return $this->where('debitAccountLabel', ...$data);
	}

	public function whereCreditAccountLabel(...$data): RecognitionModel {
		return $this->where('creditAccountLabel', ...$data);
	}

	public function whereProrataDays(...$data): RecognitionModel {
		return $this->where('prorataDays', ...$data);
	}

	public function whereComment(...$data): RecognitionModel {
		return $this->where('comment', ...$data);
	}

	public function whereCreatedAt(...$data): RecognitionModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class RecognitionCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Recognition {

		$e = new Recognition();

		if(empty($id)) {
			Recognition::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Recognition::getSelection();
		}

		if(Recognition::model()
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
			$properties = Recognition::getSelection();
		}

		if($sort !== NULL) {
			Recognition::model()->sort($sort);
		}

		return Recognition::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Recognition {

		return new Recognition(['id' => NULL]);

	}

	public static function create(Recognition $e): void {

		Recognition::model()->insert($e);

	}

	public static function update(Recognition $e, array $properties): void {

		$e->expects(['id']);

		Recognition::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Recognition $e, array $properties): void {

		Recognition::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Recognition $e): void {

		$e->expects(['id']);

		Recognition::model()->delete($e);

	}

}


class RecognitionPage extends \ModulePage {

	protected string $module = 'asset\Recognition';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? RecognitionLib::getPropertiesCreate(),
		   $propertiesUpdate ?? RecognitionLib::getPropertiesUpdate()
		);
	}

}
?>