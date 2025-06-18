<?php
namespace company;

abstract class EmployeeElement extends \Element {

	use \FilterElement;

	private static ?EmployeeModel $model = NULL;

	const ACTIVE = 'active';
	const CLOSED = 'closed';

	const OWNER = 'owner';
	const EMPLOYEE = 'employee';
	const ACCOUNTANT = 'accountant';

	const INVITED = 'invited';
	const IN = 'in';
	const OUT = 'out';

	public static function getSelection(): array {
		return Employee::model()->getProperties();
	}

	public static function model(): EmployeeModel {
		if(self::$model === NULL) {
			self::$model = new EmployeeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Employee::'.$failName, $arguments, $wrapper);
	}

}


class EmployeeModel extends \ModuleModel {

	protected string $module = 'company\Employee';
	protected string $package = 'company';
	protected string $table = 'companyEmployee';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'company' => ['element32', 'company\Company', 'cast' => 'element'],
			'companyStatus' => ['enum', [\company\Employee::ACTIVE, \company\Employee::CLOSED], 'cast' => 'enum'],
			'role' => ['enum', [\company\Employee::OWNER, \company\Employee::EMPLOYEE, \company\Employee::ACCOUNTANT], 'null' => TRUE, 'cast' => 'enum'],
			'viewFinancialYear' => ['int16', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'status' => ['enum', [\company\Employee::INVITED, \company\Employee::IN, \company\Employee::OUT], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'company', 'companyStatus', 'role', 'viewFinancialYear', 'status', 'createdAt'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'company' => 'company\Company',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'company']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'companyStatus' :
				return Employee::ACTIVE;

			case 'status' :
				return Employee::INVITED;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'companyStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'role' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): EmployeeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): EmployeeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): EmployeeModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): EmployeeModel {
		return $this->where('user', ...$data);
	}

	public function whereCompany(...$data): EmployeeModel {
		return $this->where('company', ...$data);
	}

	public function whereCompanyStatus(...$data): EmployeeModel {
		return $this->where('companyStatus', ...$data);
	}

	public function whereRole(...$data): EmployeeModel {
		return $this->where('role', ...$data);
	}

	public function whereViewFinancialYear(...$data): EmployeeModel {
		return $this->where('viewFinancialYear', ...$data);
	}

	public function whereStatus(...$data): EmployeeModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): EmployeeModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class EmployeeCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Employee {

		$e = new Employee();

		if(empty($id)) {
			Employee::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Employee::getSelection();
		}

		if(Employee::model()
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
			$properties = Employee::getSelection();
		}

		if($sort !== NULL) {
			Employee::model()->sort($sort);
		}

		return Employee::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Employee {

		return new Employee(['id' => NULL]);

	}

	public static function create(Employee $e): void {

		Employee::model()->insert($e);

	}

	public static function update(Employee $e, array $properties): void {

		$e->expects(['id']);

		Employee::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Employee $e, array $properties): void {

		Employee::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Employee $e): void {

		$e->expects(['id']);

		Employee::model()->delete($e);

	}

}


class EmployeePage extends \ModulePage {

	protected string $module = 'company\Employee';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? EmployeeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? EmployeeLib::getPropertiesUpdate()
		);
	}

}
?>