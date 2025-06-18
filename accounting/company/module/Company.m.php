<?php
namespace company;

abstract class CompanyElement extends \Element {

	use \FilterElement;

	private static ?CompanyModel $model = NULL;

	const ACTIVE = 'active';
	const CLOSED = 'closed';

	const ACCRUAL = 'accrual';
	const CASH = 'cash';

	const ACCOUNTING = 1;
	const PRODUCTION = 2;
	const SALES = 4;

	public static function getSelection(): array {
		return Company::model()->getProperties();
	}

	public static function model(): CompanyModel {
		if(self::$model === NULL) {
			self::$model = new CompanyModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Company::'.$failName, $arguments, $wrapper);
	}

}


class CompanyModel extends \ModuleModel {

	protected string $module = 'company\Company';
	protected string $package = 'company';
	protected string $table = 'company';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'vignette' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'url' => ['url', 'null' => TRUE, 'cast' => 'string'],
			'logo' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'banner' => ['textFixed', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
			'siret' => ['text8', 'min' => 14, 'max' => 14, 'null' => TRUE, 'cast' => 'string'],
			'nafCode' => ['text8', 'min' => 6, 'max' => 6, 'null' => TRUE, 'cast' => 'string'],
			'addressLine1' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'addressLine2' => ['text16', 'null' => TRUE, 'cast' => 'string'],
			'postalCode' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'city' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\company\Company::ACTIVE, \company\Company::CLOSED], 'cast' => 'enum'],
			'accountingType' => ['enum', [\company\Company::ACCRUAL, \company\Company::CASH], 'cast' => 'enum'],
			'subscriptionType' => ['set', [\company\Company::ACCOUNTING, \company\Company::PRODUCTION, \company\Company::SALES], 'null' => TRUE, 'cast' => 'set'],
			'isBio' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'vignette', 'url', 'logo', 'banner', 'siret', 'nafCode', 'addressLine1', 'addressLine2', 'postalCode', 'city', 'createdAt', 'status', 'accountingType', 'subscriptionType', 'isBio'
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Company::ACTIVE;

			case 'accountingType' :
				return Company::CASH;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'accountingType' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): CompanyModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CompanyModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CompanyModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): CompanyModel {
		return $this->where('name', ...$data);
	}

	public function whereVignette(...$data): CompanyModel {
		return $this->where('vignette', ...$data);
	}

	public function whereUrl(...$data): CompanyModel {
		return $this->where('url', ...$data);
	}

	public function whereLogo(...$data): CompanyModel {
		return $this->where('logo', ...$data);
	}

	public function whereBanner(...$data): CompanyModel {
		return $this->where('banner', ...$data);
	}

	public function whereSiret(...$data): CompanyModel {
		return $this->where('siret', ...$data);
	}

	public function whereNafCode(...$data): CompanyModel {
		return $this->where('nafCode', ...$data);
	}

	public function whereAddressLine1(...$data): CompanyModel {
		return $this->where('addressLine1', ...$data);
	}

	public function whereAddressLine2(...$data): CompanyModel {
		return $this->where('addressLine2', ...$data);
	}

	public function wherePostalCode(...$data): CompanyModel {
		return $this->where('postalCode', ...$data);
	}

	public function whereCity(...$data): CompanyModel {
		return $this->where('city', ...$data);
	}

	public function whereCreatedAt(...$data): CompanyModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): CompanyModel {
		return $this->where('status', ...$data);
	}

	public function whereAccountingType(...$data): CompanyModel {
		return $this->where('accountingType', ...$data);
	}

	public function whereSubscriptionType(...$data): CompanyModel {
		return $this->where('subscriptionType', ...$data);
	}

	public function whereIsBio(...$data): CompanyModel {
		return $this->where('isBio', ...$data);
	}


}


abstract class CompanyCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Company {

		$e = new Company();

		if(empty($id)) {
			Company::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Company::getSelection();
		}

		if(Company::model()
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
			$properties = Company::getSelection();
		}

		if($sort !== NULL) {
			Company::model()->sort($sort);
		}

		return Company::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Company {

		return new Company(['id' => NULL]);

	}

	public static function create(Company $e): void {

		Company::model()->insert($e);

	}

	public static function update(Company $e, array $properties): void {

		$e->expects(['id']);

		Company::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Company $e, array $properties): void {

		Company::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Company $e): void {

		$e->expects(['id']);

		Company::model()->delete($e);

	}

}


class CompanyPage extends \ModulePage {

	protected string $module = 'company\Company';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CompanyLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CompanyLib::getPropertiesUpdate()
		);
	}

}
?>