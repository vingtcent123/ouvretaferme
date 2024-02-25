<?php
namespace user;

abstract class CountryElement extends \Element {

	use \FilterElement;

	private static ?CountryModel $model = NULL;

	public static function getSelection(): array {
		return Country::model()->getProperties();
	}

	public static function model(): CountryModel {
		if(self::$model === NULL) {
			self::$model = new CountryModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Country::'.$failName, $arguments, $wrapper);
	}

}


class CountryModel extends \ModuleModel {

	protected string $module = 'user\Country';
	protected string $package = 'user';
	protected string $table = 'userCountry';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'code' => ['textFixed', 'min' => 2, 'max' => 2, 'unique' => TRUE, 'cast' => 'string'],
			'name' => ['text8', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'code', 'name'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['code']
		]);

	}

	public function select(...$fields): CountryModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CountryModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CountryModel {
		return $this->where('id', ...$data);
	}

	public function whereCode(...$data): CountryModel {
		return $this->where('code', ...$data);
	}

	public function whereName(...$data): CountryModel {
		return $this->where('name', ...$data);
	}


}


abstract class CountryCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Country {

		$e = new Country();

		if(empty($id)) {
			Country::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Country::getSelection();
		}

		if(Country::model()
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
			$properties = Country::getSelection();
		}

		if($sort !== NULL) {
			Country::model()->sort($sort);
		}

		return Country::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Country {

		return new Country(['id' => NULL]);

	}

	public static function create(Country $e): void {

		Country::model()->insert($e);

	}

	public static function update(Country $e, array $properties): void {

		$e->expects(['id']);

		Country::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Country $e, array $properties): void {

		Country::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Country $e): void {

		$e->expects(['id']);

		Country::model()->delete($e);

	}

}


class CountryPage extends \ModulePage {

	protected string $module = 'user\Country';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CountryLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CountryLib::getPropertiesUpdate()
		);
	}

}
?>