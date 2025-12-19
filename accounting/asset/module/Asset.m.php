<?php
namespace asset;

abstract class AssetElement extends \Element {

	use \FilterElement;

	private static ?AssetModel $model = NULL;

	const LINEAR = 'linear';
	const DEGRESSIVE = 'degressive';
	const WITHOUT = 'without';

	const ONGOING = 'ongoing';
	const SOLD = 'sold';
	const SCRAPPED = 'scrapped';
	const ENDED = 'ended';

	public static function getSelection(): array {
		return Asset::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): AssetModel {
		if(self::$model === NULL) {
			self::$model = new AssetModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Asset::'.$failName, $arguments, $wrapper);
	}

}


class AssetModel extends \ModuleModel {

	protected string $module = 'asset\Asset';
	protected string $package = 'asset';
	protected string $table = 'asset';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'account' => ['element32', 'account\Account', 'cast' => 'element'],
			'accountLabel' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'value' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0, 'max' => NULL, 'cast' => 'float'],
			'residualValue' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0, 'max' => NULL, 'cast' => 'float'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'economicMode' => ['enum', [\asset\Asset::LINEAR, \asset\Asset::DEGRESSIVE, \asset\Asset::WITHOUT], 'cast' => 'enum'],
			'economicDuration' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'economicAmortization' => ['float32', 'min' => 0, 'max' => NULL, 'cast' => 'float'],
			'fiscalMode' => ['enum', [\asset\Asset::LINEAR, \asset\Asset::DEGRESSIVE, \asset\Asset::WITHOUT], 'cast' => 'enum'],
			'fiscalDuration' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'isExcess' => ['bool', 'cast' => 'bool'],
			'excessAmortization' => ['float32', 'min' => 0, 'max' => NULL, 'cast' => 'float'],
			'excessRecovery' => ['float32', 'min' => 0, 'max' => NULL, 'cast' => 'float'],
			'resumeDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'acquisitionDate' => ['date', 'cast' => 'string'],
			'startDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'endDate' => ['date', 'cast' => 'string'],
			'endedDate' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\asset\Asset::ONGOING, \asset\Asset::SOLD, \asset\Asset::SCRAPPED, \asset\Asset::ENDED], 'cast' => 'enum'],
			'isGrant' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'account', 'accountLabel', 'value', 'residualValue', 'description', 'economicMode', 'economicDuration', 'economicAmortization', 'fiscalMode', 'fiscalDuration', 'isExcess', 'excessAmortization', 'excessRecovery', 'resumeDate', 'acquisitionDate', 'startDate', 'endDate', 'endedDate', 'status', 'isGrant', 'createdAt', 'updatedAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'account' => 'account\Account',
			'createdBy' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'residualValue' :
				return 0;

			case 'economicAmortization' :
				return 0;

			case 'isExcess' :
				return FALSE;

			case 'excessAmortization' :
				return 0;

			case 'excessRecovery' :
				return 0;

			case 'status' :
				return Asset::ONGOING;

			case 'isGrant' :
				return FALSE;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'economicMode' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'fiscalMode' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): AssetModel {
		return parent::select(...$fields);
	}

	public function where(...$data): AssetModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): AssetModel {
		return $this->where('id', ...$data);
	}

	public function whereAccount(...$data): AssetModel {
		return $this->where('account', ...$data);
	}

	public function whereAccountLabel(...$data): AssetModel {
		return $this->where('accountLabel', ...$data);
	}

	public function whereValue(...$data): AssetModel {
		return $this->where('value', ...$data);
	}

	public function whereResidualValue(...$data): AssetModel {
		return $this->where('residualValue', ...$data);
	}

	public function whereDescription(...$data): AssetModel {
		return $this->where('description', ...$data);
	}

	public function whereEconomicMode(...$data): AssetModel {
		return $this->where('economicMode', ...$data);
	}

	public function whereEconomicDuration(...$data): AssetModel {
		return $this->where('economicDuration', ...$data);
	}

	public function whereEconomicAmortization(...$data): AssetModel {
		return $this->where('economicAmortization', ...$data);
	}

	public function whereFiscalMode(...$data): AssetModel {
		return $this->where('fiscalMode', ...$data);
	}

	public function whereFiscalDuration(...$data): AssetModel {
		return $this->where('fiscalDuration', ...$data);
	}

	public function whereIsExcess(...$data): AssetModel {
		return $this->where('isExcess', ...$data);
	}

	public function whereExcessAmortization(...$data): AssetModel {
		return $this->where('excessAmortization', ...$data);
	}

	public function whereExcessRecovery(...$data): AssetModel {
		return $this->where('excessRecovery', ...$data);
	}

	public function whereResumeDate(...$data): AssetModel {
		return $this->where('resumeDate', ...$data);
	}

	public function whereAcquisitionDate(...$data): AssetModel {
		return $this->where('acquisitionDate', ...$data);
	}

	public function whereStartDate(...$data): AssetModel {
		return $this->where('startDate', ...$data);
	}

	public function whereEndDate(...$data): AssetModel {
		return $this->where('endDate', ...$data);
	}

	public function whereEndedDate(...$data): AssetModel {
		return $this->where('endedDate', ...$data);
	}

	public function whereStatus(...$data): AssetModel {
		return $this->where('status', ...$data);
	}

	public function whereIsGrant(...$data): AssetModel {
		return $this->where('isGrant', ...$data);
	}

	public function whereCreatedAt(...$data): AssetModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): AssetModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereCreatedBy(...$data): AssetModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class AssetCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Asset {

		$e = new Asset();

		if(empty($id)) {
			Asset::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Asset::getSelection();
		}

		if(Asset::model()
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
			$properties = Asset::getSelection();
		}

		if($sort !== NULL) {
			Asset::model()->sort($sort);
		}

		return Asset::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Asset {

		return new Asset(['id' => NULL]);

	}

	public static function create(Asset $e): void {

		Asset::model()->insert($e);

	}

	public static function update(Asset $e, array $properties): void {

		$e->expects(['id']);

		Asset::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Asset $e, array $properties): void {

		Asset::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Asset $e): void {

		$e->expects(['id']);

		Asset::model()->delete($e);

	}

}


class AssetPage extends \ModulePage {

	protected string $module = 'asset\Asset';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? AssetLib::getPropertiesCreate(),
		   $propertiesUpdate ?? AssetLib::getPropertiesUpdate()
		);
	}

}
?>