<?php
namespace accounting;

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

	protected string $module = 'accounting\Account';
	protected string $package = 'accounting';
	protected string $table = 'accountingAccount';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'class' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'collate' => 'general', 'cast' => 'string'],
			'visible' => ['bool', 'cast' => 'bool'],
			'custom' => ['bool', 'cast' => 'bool'],
			'vatAccount' => ['element32', 'accounting\Account', 'null' => TRUE, 'cast' => 'element'],
			'vatRate' => ['decimal', 'digits' => 5, 'decimal' => 2, 'null' => TRUE, 'cast' => 'float'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'class', 'description', 'visible', 'custom', 'vatAccount', 'vatRate', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'vatAccount' => 'accounting\Account',
			'createdBy' => 'user\User',
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

			case 'custom' :
				return FALSE;

			case 'vatRate' :
				return 0;

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

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

	public function whereClass(...$data): AccountModel {
		return $this->where('class', ...$data);
	}

	public function whereDescription(...$data): AccountModel {
		return $this->where('description', ...$data);
	}

	public function whereVisible(...$data): AccountModel {
		return $this->where('visible', ...$data);
	}

	public function whereCustom(...$data): AccountModel {
		return $this->where('custom', ...$data);
	}

	public function whereVatAccount(...$data): AccountModel {
		return $this->where('vatAccount', ...$data);
	}

	public function whereVatRate(...$data): AccountModel {
		return $this->where('vatRate', ...$data);
	}

	public function whereCreatedAt(...$data): AccountModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): AccountModel {
		return $this->where('createdBy', ...$data);
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

	protected string $module = 'accounting\Account';

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