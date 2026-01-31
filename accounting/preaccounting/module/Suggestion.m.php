<?php
namespace preaccounting;

abstract class SuggestionElement extends \Element {

	use \FilterElement;

	private static ?SuggestionModel $model = NULL;

	const WAITING = 'waiting';
	const REJECTED = 'rejected';
	const VALIDATED = 'validated';
	const OUT = 'out';

	const AMOUNT = 1;
	const THIRD_PARTY = 2;
	const REFERENCE = 4;
	const DATE = 8;
	const PAYMENT_METHOD = 16;

	public static function getSelection(): array {
		return Suggestion::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): SuggestionModel {
		if(self::$model === NULL) {
			self::$model = new SuggestionModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Suggestion::'.$failName, $arguments, $wrapper);
	}

}


class SuggestionModel extends \ModuleModel {

	protected string $module = 'preaccounting\Suggestion';
	protected string $package = 'preaccounting';
	protected string $table = 'preaccountingSuggestion';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'cashflow' => ['element32', 'bank\Cashflow', 'cast' => 'element'],
			'invoice' => ['element32', 'selling\Invoice', 'cast' => 'element'],
			'paymentMethod' => ['element32', 'payment\Method', 'null' => TRUE, 'cast' => 'element'],
			'status' => ['enum', [\preaccounting\Suggestion::WAITING, \preaccounting\Suggestion::REJECTED, \preaccounting\Suggestion::VALIDATED, \preaccounting\Suggestion::OUT], 'cast' => 'enum'],
			'reason' => ['set', [\preaccounting\Suggestion::AMOUNT, \preaccounting\Suggestion::THIRD_PARTY, \preaccounting\Suggestion::REFERENCE, \preaccounting\Suggestion::DATE, \preaccounting\Suggestion::PAYMENT_METHOD], 'cast' => 'set'],
			'weight' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'cashflow', 'invoice', 'paymentMethod', 'status', 'reason', 'weight', 'createdAt'
		]);

		$this->propertiesToModule += [
			'cashflow' => 'bank\Cashflow',
			'invoice' => 'selling\Invoice',
			'paymentMethod' => 'payment\Method',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['cashflow', 'invoice']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Suggestion::WAITING;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): SuggestionModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SuggestionModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SuggestionModel {
		return $this->where('id', ...$data);
	}

	public function whereCashflow(...$data): SuggestionModel {
		return $this->where('cashflow', ...$data);
	}

	public function whereInvoice(...$data): SuggestionModel {
		return $this->where('invoice', ...$data);
	}

	public function wherePaymentMethod(...$data): SuggestionModel {
		return $this->where('paymentMethod', ...$data);
	}

	public function whereStatus(...$data): SuggestionModel {
		return $this->where('status', ...$data);
	}

	public function whereReason(...$data): SuggestionModel {
		return $this->where('reason', ...$data);
	}

	public function whereWeight(...$data): SuggestionModel {
		return $this->where('weight', ...$data);
	}

	public function whereCreatedAt(...$data): SuggestionModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class SuggestionCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Suggestion {

		$e = new Suggestion();

		if(empty($id)) {
			Suggestion::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Suggestion::getSelection();
		}

		if(Suggestion::model()
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
			$properties = Suggestion::getSelection();
		}

		if($sort !== NULL) {
			Suggestion::model()->sort($sort);
		}

		return Suggestion::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Suggestion {

		return new Suggestion($properties);

	}

	public static function create(Suggestion $e): void {

		Suggestion::model()->insert($e);

	}

	public static function update(Suggestion $e, array $properties): void {

		$e->expects(['id']);

		Suggestion::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Suggestion $e, array $properties): void {

		Suggestion::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Suggestion $e): void {

		$e->expects(['id']);

		Suggestion::model()->delete($e);

	}

}


class SuggestionPage extends \ModulePage {

	protected string $module = 'preaccounting\Suggestion';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SuggestionLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SuggestionLib::getPropertiesUpdate()
		);
	}

}
?>