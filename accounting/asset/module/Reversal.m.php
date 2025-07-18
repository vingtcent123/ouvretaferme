<?php
namespace asset;

abstract class ReversalElement extends \Element {

	use \FilterElement;

	private static ?ReversalModel $model = NULL;

	public static function getSelection(): array {
		return Reversal::model()->getProperties();
	}

	public static function model(): ReversalModel {
		if(self::$model === NULL) {
			self::$model = new ReversalModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Reversal::'.$failName, $arguments, $wrapper);
	}

}


class ReversalModel extends \ModuleModel {

	protected string $module = 'asset\Reversal';
	protected string $package = 'asset';
	protected string $table = 'assetReversal';

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

	public function select(...$fields): ReversalModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ReversalModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ReversalModel {
		return $this->where('id', ...$data);
	}

	public function whereGrant(...$data): ReversalModel {
		return $this->where('grant', ...$data);
	}

	public function whereFinancialYear(...$data): ReversalModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereDate(...$data): ReversalModel {
		return $this->where('date', ...$data);
	}

	public function whereAmount(...$data): ReversalModel {
		return $this->where('amount', ...$data);
	}

	public function whereOperation(...$data): ReversalModel {
		return $this->where('operation', ...$data);
	}

	public function whereDebitAccountLabel(...$data): ReversalModel {
		return $this->where('debitAccountLabel', ...$data);
	}

	public function whereCreditAccountLabel(...$data): ReversalModel {
		return $this->where('creditAccountLabel', ...$data);
	}

	public function whereProrataDays(...$data): ReversalModel {
		return $this->where('prorataDays', ...$data);
	}

	public function whereComment(...$data): ReversalModel {
		return $this->where('comment', ...$data);
	}

	public function whereCreatedAt(...$data): ReversalModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class ReversalCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Reversal {

		$e = new Reversal();

		if(empty($id)) {
			Reversal::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Reversal::getSelection();
		}

		if(Reversal::model()
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
			$properties = Reversal::getSelection();
		}

		if($sort !== NULL) {
			Reversal::model()->sort($sort);
		}

		return Reversal::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Reversal {

		return new Reversal(['id' => NULL]);

	}

	public static function create(Reversal $e): void {

		Reversal::model()->insert($e);

	}

	public static function update(Reversal $e, array $properties): void {

		$e->expects(['id']);

		Reversal::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Reversal $e, array $properties): void {

		Reversal::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Reversal $e): void {

		$e->expects(['id']);

		Reversal::model()->delete($e);

	}

}


class ReversalPage extends \ModulePage {

	protected string $module = 'asset\Reversal';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ReversalLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ReversalLib::getPropertiesUpdate()
		);
	}

}
?>