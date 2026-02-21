<?php
namespace pdp;

abstract class AddressElement extends \Element {

	use \FilterElement;

	private static ?AddressModel $model = NULL;

	const SENDING = 'sending';
	const PENDING = 'pending';
	const CREATED = 'created';
	const ERROR = 'error';

	const PEPPOL = 'peppol';
	const PPF = 'ppf';

	public static function getSelection(): array {
		return Address::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): AddressModel {
		if(self::$model === NULL) {
			self::$model = new AddressModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Address::'.$failName, $arguments, $wrapper);
	}

}


class AddressModel extends \ModuleModel {

	protected string $module = 'pdp\Address';
	protected string $package = 'pdp';
	protected string $table = 'pdpAddress';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'identifier' => ['text8', 'min' => 0, 'max' => NULL, 'cast' => 'string'],
			'company' => ['element32', 'pdp\Company', 'cast' => 'element'],
			'status' => ['enum', [\pdp\Address::SENDING, \pdp\Address::PENDING, \pdp\Address::CREATED, \pdp\Address::ERROR], 'cast' => 'enum'],
			'statusMessage' => ['text24', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'type' => ['enum', [\pdp\Address::PEPPOL, \pdp\Address::PPF], 'cast' => 'enum'],
			'isReplyTo' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'identifier', 'company', 'status', 'statusMessage', 'type', 'isReplyTo', 'createdAt'
		]);

		$this->propertiesToModule += [
			'company' => 'pdp\Company',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return Address::SENDING;

			case 'type' :
				return Address::PEPPOL;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): AddressModel {
		return parent::select(...$fields);
	}

	public function where(...$data): AddressModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): AddressModel {
		return $this->where('id', ...$data);
	}

	public function whereIdentifier(...$data): AddressModel {
		return $this->where('identifier', ...$data);
	}

	public function whereCompany(...$data): AddressModel {
		return $this->where('company', ...$data);
	}

	public function whereStatus(...$data): AddressModel {
		return $this->where('status', ...$data);
	}

	public function whereStatusMessage(...$data): AddressModel {
		return $this->where('statusMessage', ...$data);
	}

	public function whereType(...$data): AddressModel {
		return $this->where('type', ...$data);
	}

	public function whereIsReplyTo(...$data): AddressModel {
		return $this->where('isReplyTo', ...$data);
	}

	public function whereCreatedAt(...$data): AddressModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class AddressCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Address {

		$e = new Address();

		if(empty($id)) {
			Address::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Address::getSelection();
		}

		if(Address::model()
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
			$properties = Address::getSelection();
		}

		if($sort !== NULL) {
			Address::model()->sort($sort);
		}

		return Address::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Address {

		return new Address($properties);

	}

	public static function create(Address $e): void {

		Address::model()->insert($e);

	}

	public static function update(Address $e, array $properties): void {

		$e->expects(['id']);

		Address::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Address $e, array $properties): void {

		Address::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Address $e): void {

		$e->expects(['id']);

		Address::model()->delete($e);

	}

}


class AddressPage extends \ModulePage {

	protected string $module = 'pdp\Address';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? AddressLib::getPropertiesCreate(),
		   $propertiesUpdate ?? AddressLib::getPropertiesUpdate()
		);
	}

}
?>