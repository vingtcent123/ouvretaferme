<?php
namespace company;

abstract class GenericAccountElement extends \Element {

	use \FilterElement;

	private static ?GenericAccountModel $model = NULL;

	public static function getSelection(): array {
		return GenericAccount::model()->getProperties();
	}

	public static function model(): GenericAccountModel {
		if(self::$model === NULL) {
			self::$model = new GenericAccountModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('GenericAccount::'.$failName, $arguments, $wrapper);
	}

}


class GenericAccountModel extends \ModuleModel {

	protected string $module = 'company\GenericAccount';
	protected string $package = 'company';
	protected string $table = 'companyGenericAccount';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'class' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'visible' => ['bool', 'cast' => 'bool'],
			'vatAccount' => ['element32', 'account\Account', 'null' => TRUE, 'cast' => 'element'],
			'vatRate' => ['decimal', 'digits' => 5, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'class', 'description', 'visible', 'vatAccount', 'vatRate'
		]);

		$this->propertiesToModule += [
			'vatAccount' => 'account\Account',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['id'],
			['class']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'visible' :
				return TRUE;

			case 'vatRate' :
				return 0;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): GenericAccountModel {
		return parent::select(...$fields);
	}

	public function where(...$data): GenericAccountModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): GenericAccountModel {
		return $this->where('id', ...$data);
	}

	public function whereClass(...$data): GenericAccountModel {
		return $this->where('class', ...$data);
	}

	public function whereDescription(...$data): GenericAccountModel {
		return $this->where('description', ...$data);
	}

	public function whereVisible(...$data): GenericAccountModel {
		return $this->where('visible', ...$data);
	}

	public function whereVatAccount(...$data): GenericAccountModel {
		return $this->where('vatAccount', ...$data);
	}

	public function whereVatRate(...$data): GenericAccountModel {
		return $this->where('vatRate', ...$data);
	}


}


abstract class GenericAccountCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): GenericAccount {

		$e = new GenericAccount();

		if(empty($id)) {
			GenericAccount::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = GenericAccount::getSelection();
		}

		if(GenericAccount::model()
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
			$properties = GenericAccount::getSelection();
		}

		if($sort !== NULL) {
			GenericAccount::model()->sort($sort);
		}

		return GenericAccount::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): GenericAccount {

		return new GenericAccount(['id' => NULL]);

	}

	public static function create(GenericAccount $e): void {

		GenericAccount::model()->insert($e);

	}

	public static function update(GenericAccount $e, array $properties): void {

		$e->expects(['id']);

		GenericAccount::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, GenericAccount $e, array $properties): void {

		GenericAccount::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(GenericAccount $e): void {

		$e->expects(['id']);

		GenericAccount::model()->delete($e);

	}

}


class GenericAccountPage extends \ModulePage {

	protected string $module = 'company\GenericAccount';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? GenericAccountLib::getPropertiesCreate(),
		   $propertiesUpdate ?? GenericAccountLib::getPropertiesUpdate()
		);
	}

}
?>