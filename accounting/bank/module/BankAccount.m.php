<?php
namespace bank;

abstract class BankAccountElement extends \Element {

	use \FilterElement;

	private static ?BankAccountModel $model = NULL;

	public static function getSelection(): array {
		return BankAccount::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): BankAccountModel {
		if(self::$model === NULL) {
			self::$model = new BankAccountModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('BankAccount::'.$failName, $arguments, $wrapper);
	}

}


class BankAccountModel extends \ModuleModel {

	protected string $module = 'bank\BankAccount';
	protected string $package = 'bank';
	protected string $table = 'bankBankAccount';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'bankId' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'accountId' => ['text8', 'min' => 1, 'max' => NULL, 'unique' => TRUE, 'cast' => 'string'],
			'label' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'isDefault' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'bankId', 'accountId', 'label', 'description', 'isDefault'
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

	public function select(...$fields): BankAccountModel {
		return parent::select(...$fields);
	}

	public function where(...$data): BankAccountModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): BankAccountModel {
		return $this->where('id', ...$data);
	}

	public function whereBankId(...$data): BankAccountModel {
		return $this->where('bankId', ...$data);
	}

	public function whereAccountId(...$data): BankAccountModel {
		return $this->where('accountId', ...$data);
	}

	public function whereLabel(...$data): BankAccountModel {
		return $this->where('label', ...$data);
	}

	public function whereDescription(...$data): BankAccountModel {
		return $this->where('description', ...$data);
	}

	public function whereIsDefault(...$data): BankAccountModel {
		return $this->where('isDefault', ...$data);
	}


}


abstract class BankAccountCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): BankAccount {

		$e = new BankAccount();

		if(empty($id)) {
			BankAccount::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = BankAccount::getSelection();
		}

		if(BankAccount::model()
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
			$properties = BankAccount::getSelection();
		}

		if($sort !== NULL) {
			BankAccount::model()->sort($sort);
		}

		return BankAccount::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): BankAccount {

		return new BankAccount(['id' => NULL]);

	}

	public static function create(BankAccount $e): void {

		BankAccount::model()->insert($e);

	}

	public static function update(BankAccount $e, array $properties): void {

		$e->expects(['id']);

		BankAccount::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, BankAccount $e, array $properties): void {

		BankAccount::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(BankAccount $e): void {

		$e->expects(['id']);

		BankAccount::model()->delete($e);

	}

}


class BankAccountPage extends \ModulePage {

	protected string $module = 'bank\BankAccount';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? BankAccountLib::getPropertiesCreate(),
		   $propertiesUpdate ?? BankAccountLib::getPropertiesUpdate()
		);
	}

}
?>