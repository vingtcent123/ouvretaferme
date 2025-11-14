<?php
namespace asset;

abstract class AmortizationElement extends \Element {

	use \FilterElement;

	private static ?AmortizationModel $model = NULL;

	const ECONOMIC = 'economic';
	const EXCESS = 'excess';

	public static function getSelection(): array {
		return Amortization::model()->getProperties();
	}

	public static function model(): AmortizationModel {
		if(self::$model === NULL) {
			self::$model = new AmortizationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Amortization::'.$failName, $arguments, $wrapper);
	}

}


class AmortizationModel extends \ModuleModel {

	protected string $module = 'asset\Amortization';
	protected string $package = 'asset';
	protected string $table = 'assetAmortization';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'asset' => ['element32', 'asset\Asset', 'cast' => 'element'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'type' => ['enum', [\asset\Amortization::ECONOMIC, \asset\Amortization::EXCESS], 'cast' => 'enum'],
			'date' => ['date', 'cast' => 'string'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'asset', 'amount', 'type', 'date', 'financialYear', 'createdAt'
		]);

		$this->propertiesToModule += [
			'asset' => 'asset\Asset',
			'financialYear' => 'account\FinancialYear',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'date' :
				return new \Sql('CURDATE()');

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): AmortizationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): AmortizationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): AmortizationModel {
		return $this->where('id', ...$data);
	}

	public function whereAsset(...$data): AmortizationModel {
		return $this->where('asset', ...$data);
	}

	public function whereAmount(...$data): AmortizationModel {
		return $this->where('amount', ...$data);
	}

	public function whereType(...$data): AmortizationModel {
		return $this->where('type', ...$data);
	}

	public function whereDate(...$data): AmortizationModel {
		return $this->where('date', ...$data);
	}

	public function whereFinancialYear(...$data): AmortizationModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereCreatedAt(...$data): AmortizationModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class AmortizationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Amortization {

		$e = new Amortization();

		if(empty($id)) {
			Amortization::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Amortization::getSelection();
		}

		if(Amortization::model()
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
			$properties = Amortization::getSelection();
		}

		if($sort !== NULL) {
			Amortization::model()->sort($sort);
		}

		return Amortization::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Amortization {

		return new Amortization(['id' => NULL]);

	}

	public static function create(Amortization $e): void {

		Amortization::model()->insert($e);

	}

	public static function update(Amortization $e, array $properties): void {

		$e->expects(['id']);

		Amortization::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Amortization $e, array $properties): void {

		Amortization::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Amortization $e): void {

		$e->expects(['id']);

		Amortization::model()->delete($e);

	}

}


class AmortizationPage extends \ModulePage {

	protected string $module = 'asset\Amortization';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? AmortizationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? AmortizationLib::getPropertiesUpdate()
		);
	}

}
?>