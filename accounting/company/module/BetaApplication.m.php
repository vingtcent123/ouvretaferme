<?php
namespace company;

abstract class BetaApplicationElement extends \Element {

	use \FilterElement;

	private static ?BetaApplicationModel $model = NULL;

	const ACCRUAL = 'accrual';
	const CASH = 'cash';
	const CASH_ACCRUAL = 'cash-accrual';

	const MICRO_BA = 'micro-ba';
	const BA_REEL_SIMPLIFIE = 'ba-reel-simplifie';
	const BA_REEL_NORMAL = 'ba-reel-normal';
	const OTHER_BIC = 'other-bic';
	const OTHER_BNC = 'other-bnc';
	const OTHER = 'other';

	const MONTHLY = 'monthly';
	const QUARTERLY = 'quarterly';
	const ANNUALLY = 'annually';

	const BEGINNER = 'beginner';
	const INITIATED = 'initiated';
	const COMFORTABLE = 'comfortable';
	const EXPERT = 'expert';

	public static function getSelection(): array {
		return BetaApplication::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): BetaApplicationModel {
		if(self::$model === NULL) {
			self::$model = new BetaApplicationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('BetaApplication::'.$failName, $arguments, $wrapper);
	}

}


class BetaApplicationModel extends \ModuleModel {

	protected string $module = 'company\BetaApplication';
	protected string $package = 'company';
	protected string $table = 'companyBetaApplication';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'farm' => ['element32', 'farm\Farm', 'unique' => TRUE, 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'accountingType' => ['enum', [\company\BetaApplication::ACCRUAL, \company\BetaApplication::CASH, \company\BetaApplication::CASH_ACCRUAL], 'cast' => 'enum'],
			'taxSystem' => ['enum', [\company\BetaApplication::MICRO_BA, \company\BetaApplication::BA_REEL_SIMPLIFIE, \company\BetaApplication::BA_REEL_NORMAL, \company\BetaApplication::OTHER_BIC, \company\BetaApplication::OTHER_BNC, \company\BetaApplication::OTHER], 'cast' => 'enum'],
			'hasVat' => ['bool', 'cast' => 'bool'],
			'vatFrequency' => ['enum', [\company\BetaApplication::MONTHLY, \company\BetaApplication::QUARTERLY, \company\BetaApplication::ANNUALLY], 'null' => TRUE, 'cast' => 'enum'],
			'discord' => ['bool', 'cast' => 'bool'],
			'accountingHelped' => ['bool', 'cast' => 'bool'],
			'helpComment' => ['text8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'accountingLevel' => ['enum', [\company\BetaApplication::BEGINNER, \company\BetaApplication::INITIATED, \company\BetaApplication::COMFORTABLE, \company\BetaApplication::EXPERT], 'cast' => 'enum'],
			'hasSoftware' => ['bool', 'cast' => 'bool'],
			'software' => ['text8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'hasStocks' => ['bool', 'cast' => 'bool'],
			'comment' => ['text16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'farm', 'user', 'accountingType', 'taxSystem', 'hasVat', 'vatFrequency', 'discord', 'accountingHelped', 'helpComment', 'accountingLevel', 'hasSoftware', 'software', 'hasStocks', 'comment', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'user' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'user' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'accountingType' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'taxSystem' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'vatFrequency' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'accountingLevel' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): BetaApplicationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): BetaApplicationModel {
		return parent::where(...$data);
	}

	public function whereFarm(...$data): BetaApplicationModel {
		return $this->where('farm', ...$data);
	}

	public function whereUser(...$data): BetaApplicationModel {
		return $this->where('user', ...$data);
	}

	public function whereAccountingType(...$data): BetaApplicationModel {
		return $this->where('accountingType', ...$data);
	}

	public function whereTaxSystem(...$data): BetaApplicationModel {
		return $this->where('taxSystem', ...$data);
	}

	public function whereHasVat(...$data): BetaApplicationModel {
		return $this->where('hasVat', ...$data);
	}

	public function whereVatFrequency(...$data): BetaApplicationModel {
		return $this->where('vatFrequency', ...$data);
	}

	public function whereDiscord(...$data): BetaApplicationModel {
		return $this->where('discord', ...$data);
	}

	public function whereAccountingHelped(...$data): BetaApplicationModel {
		return $this->where('accountingHelped', ...$data);
	}

	public function whereHelpComment(...$data): BetaApplicationModel {
		return $this->where('helpComment', ...$data);
	}

	public function whereAccountingLevel(...$data): BetaApplicationModel {
		return $this->where('accountingLevel', ...$data);
	}

	public function whereHasSoftware(...$data): BetaApplicationModel {
		return $this->where('hasSoftware', ...$data);
	}

	public function whereSoftware(...$data): BetaApplicationModel {
		return $this->where('software', ...$data);
	}

	public function whereHasStocks(...$data): BetaApplicationModel {
		return $this->where('hasStocks', ...$data);
	}

	public function whereComment(...$data): BetaApplicationModel {
		return $this->where('comment', ...$data);
	}

	public function whereCreatedAt(...$data): BetaApplicationModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class BetaApplicationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): BetaApplication {

		$e = new BetaApplication();

		if(empty($id)) {
			BetaApplication::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = BetaApplication::getSelection();
		}

		if(BetaApplication::model()
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
			$properties = BetaApplication::getSelection();
		}

		if($sort !== NULL) {
			BetaApplication::model()->sort($sort);
		}

		return BetaApplication::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): BetaApplication {

		return new BetaApplication(['id' => NULL]);

	}

	public static function create(BetaApplication $e): void {

		BetaApplication::model()->insert($e);

	}

	public static function update(BetaApplication $e, array $properties): void {

		$e->expects(['id']);

		BetaApplication::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, BetaApplication $e, array $properties): void {

		BetaApplication::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(BetaApplication $e): void {

		$e->expects(['id']);

		BetaApplication::model()->delete($e);

	}

}


class BetaApplicationPage extends \ModulePage {

	protected string $module = 'company\BetaApplication';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? BetaApplicationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? BetaApplicationLib::getPropertiesUpdate()
		);
	}

}
?>