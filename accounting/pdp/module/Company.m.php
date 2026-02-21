<?php
namespace pdp;

abstract class CompanyElement extends \Element {

	use \FilterElement;

	private static ?CompanyModel $model = NULL;

	public static function getSelection(): array {
		return Company::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'pdp\Company';
	protected string $package = 'pdp';
	protected string $table = 'pdpCompany';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'number' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'formal_name' => ['text8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'address' => ['text24', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'postcode' => ['text8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'city' => ['text8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'number', 'formal_name', 'address', 'postcode', 'city'
		]);

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

	public function whereNumber(...$data): CompanyModel {
		return $this->where('number', ...$data);
	}

	public function whereFormal_name(...$data): CompanyModel {
		return $this->where('formal_name', ...$data);
	}

	public function whereAddress(...$data): CompanyModel {
		return $this->where('address', ...$data);
	}

	public function wherePostcode(...$data): CompanyModel {
		return $this->where('postcode', ...$data);
	}

	public function whereCity(...$data): CompanyModel {
		return $this->where('city', ...$data);
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

	public static function getNewElement(array $properties = []): Company {

		return new Company($properties);

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

	protected string $module = 'pdp\Company';

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