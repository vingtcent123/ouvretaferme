<?php
namespace bank;

abstract class AccountElement extends \Element {

	use \FilterElement;

	private static ?AccountModel $model = NULL;

	public static function getSelection(): array {
		return Account::model()->getProperties();
	}

	public static function model(): AccountModel {
		if(self::$model === NULL) {
			self::$model = new AccountModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Account::'.$failName, $arguments, $wrapper);
	}

}


class AccountModel extends \ModuleModel {

	protected string $module = 'bank\Account';
	protected string $package = 'bank';
	protected string $table = 'bankAccount';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'bankId' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'accountId' => ['text8', 'min' => 1, 'max' => NULL, 'unique' => TRUE, 'cast' => 'string'],
			'label' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'isDefault' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'bankId', 'accountId', 'label', 'isDefault'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['accountId']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'isDefault' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): AccountModel {
		return parent::select(...$fields);
	}

	public function where(...$data): AccountModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): AccountModel {
		return $this->where('id', ...$data);
	}

	public function whereBankId(...$data): AccountModel {
		return $this->where('bankId', ...$data);
	}

	public function whereAccountId(...$data): AccountModel {
		return $this->where('accountId', ...$data);
	}

	public function whereLabel(...$data): AccountModel {
		return $this->where('label', ...$data);
	}

	public function whereIsDefault(...$data): AccountModel {
		return $this->where('isDefault', ...$data);
	}


}


abstract class AccountCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Account {

		$e = new Account();

		if(empty($id)) {
			Account::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Account::getSelection();
		}

		if(Account::model()
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
			$properties = Account::getSelection();
		}

		if($sort !== NULL) {
			Account::model()->sort($sort);
		}

		return Account::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Account {

		return new Account(['id' => NULL]);

	}

	public static function create(Account $e): void {

		Account::model()->insert($e);

	}

	public static function update(Account $e, array $properties): void {

		$e->expects(['id']);

		Account::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Account $e, array $properties): void {

		Account::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Account $e): void {

		$e->expects(['id']);

		Account::model()->delete($e);

	}

}


class AccountPage extends \ModulePage {

	protected string $module = 'bank\Account';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? AccountLib::getPropertiesCreate(),
		   $propertiesUpdate ?? AccountLib::getPropertiesUpdate()
		);
	}

}
?>