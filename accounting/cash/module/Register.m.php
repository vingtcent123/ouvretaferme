<?php
namespace cash;

abstract class RegisterElement extends \Element {

	use \FilterElement;

	private static ?RegisterModel $model = NULL;

	const ACTIVE = 'active';
	const DELETED = 'deleted';

	public static function getSelection(): array {
		return Register::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): RegisterModel {
		if(self::$model === NULL) {
			self::$model = new RegisterModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Register::'.$failName, $arguments, $wrapper);
	}

}


class RegisterModel extends \ModuleModel {

	protected string $module = 'cash\Register';
	protected string $package = 'cash';
	protected string $table = 'cashRegister';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'account' => ['element32', 'account\Account', 'null' => TRUE, 'cast' => 'element'],
			'paymentMethod' => ['element32', 'payment\Method', 'cast' => 'element'],
			'status' => ['enum', [\cash\Register::ACTIVE, \cash\Register::DELETED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'account', 'paymentMethod', 'status'
		]);

		$this->propertiesToModule += [
			'account' => 'account\Account',
			'paymentMethod' => 'payment\Method',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Register::ACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): RegisterModel {
		return parent::select(...$fields);
	}

	public function where(...$data): RegisterModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): RegisterModel {
		return $this->where('id', ...$data);
	}

	public function whereAccount(...$data): RegisterModel {
		return $this->where('account', ...$data);
	}

	public function wherePaymentMethod(...$data): RegisterModel {
		return $this->where('paymentMethod', ...$data);
	}

	public function whereStatus(...$data): RegisterModel {
		return $this->where('status', ...$data);
	}


}


abstract class RegisterCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Register {

		$e = new Register();

		if(empty($id)) {
			Register::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Register::getSelection();
		}

		if(Register::model()
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
			$properties = Register::getSelection();
		}

		if($sort !== NULL) {
			Register::model()->sort($sort);
		}

		return Register::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Register {

		return new Register($properties);

	}

	public static function create(Register $e): void {

		Register::model()->insert($e);

	}

	public static function update(Register $e, array $properties): void {

		$e->expects(['id']);

		Register::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Register $e, array $properties): void {

		Register::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Register $e): void {

		$e->expects(['id']);

		Register::model()->delete($e);

	}

}


class RegisterPage extends \ModulePage {

	protected string $module = 'cash\Register';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? RegisterLib::getPropertiesCreate(),
		   $propertiesUpdate ?? RegisterLib::getPropertiesUpdate()
		);
	}

}
?>