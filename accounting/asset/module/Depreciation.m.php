<?php
namespace asset;

abstract class DepreciationElement extends \Element {

	use \FilterElement;

	private static ?DepreciationModel $model = NULL;

	const ECONOMIC = 'economic';
	const EXCESS = 'excess';

	public static function getSelection(): array {
		return Depreciation::model()->getProperties();
	}

	public static function model(): DepreciationModel {
		if(self::$model === NULL) {
			self::$model = new DepreciationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Depreciation::'.$failName, $arguments, $wrapper);
	}

}


class DepreciationModel extends \ModuleModel {

	protected string $module = 'asset\Depreciation';
	protected string $package = 'asset';
	protected string $table = 'assetDepreciation';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'asset' => ['element32', 'asset\Asset', 'cast' => 'element'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'type' => ['enum', [\asset\Depreciation::ECONOMIC, \asset\Depreciation::EXCESS], 'cast' => 'enum'],
			'date' => ['date', 'cast' => 'string'],
			'financialYear' => ['element32', 'accounting\FinancialYear', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'asset', 'amount', 'type', 'date', 'financialYear', 'createdAt'
		]);

		$this->propertiesToModule += [
			'asset' => 'asset\Asset',
			'financialYear' => 'accounting\FinancialYear',
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

	public function select(...$fields): DepreciationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DepreciationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DepreciationModel {
		return $this->where('id', ...$data);
	}

	public function whereAsset(...$data): DepreciationModel {
		return $this->where('asset', ...$data);
	}

	public function whereAmount(...$data): DepreciationModel {
		return $this->where('amount', ...$data);
	}

	public function whereType(...$data): DepreciationModel {
		return $this->where('type', ...$data);
	}

	public function whereDate(...$data): DepreciationModel {
		return $this->where('date', ...$data);
	}

	public function whereFinancialYear(...$data): DepreciationModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereCreatedAt(...$data): DepreciationModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class DepreciationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Depreciation {

		$e = new Depreciation();

		if(empty($id)) {
			Depreciation::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Depreciation::getSelection();
		}

		if(Depreciation::model()
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
			$properties = Depreciation::getSelection();
		}

		if($sort !== NULL) {
			Depreciation::model()->sort($sort);
		}

		return Depreciation::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Depreciation {

		return new Depreciation(['id' => NULL]);

	}

	public static function create(Depreciation $e): void {

		Depreciation::model()->insert($e);

	}

	public static function update(Depreciation $e, array $properties): void {

		$e->expects(['id']);

		Depreciation::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Depreciation $e, array $properties): void {

		Depreciation::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Depreciation $e): void {

		$e->expects(['id']);

		Depreciation::model()->delete($e);

	}

}


class DepreciationPage extends \ModulePage {

	protected string $module = 'asset\Depreciation';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DepreciationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DepreciationLib::getPropertiesUpdate()
		);
	}

}
?>