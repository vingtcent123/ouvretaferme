<?php
namespace farm;

abstract class InviteElement extends \Element {

	use \FilterElement;

	private static ?InviteModel $model = NULL;

	const FARMER = 'farmer';
	const CUSTOMER = 'customer';

	const PENDING = 'pending';
	const ACCEPTED = 'accepted';

	public static function getSelection(): array {
		return Invite::model()->getProperties();
	}

	public static function model(): InviteModel {
		if(self::$model === NULL) {
			self::$model = new InviteModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Invite::'.$failName, $arguments, $wrapper);
	}

}


class InviteModel extends \ModuleModel {

	protected string $module = 'farm\Invite';
	protected string $package = 'farm';
	protected string $table = 'farmInvite';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'email' => ['email', 'cast' => 'string'],
			'type' => ['enum', [\farm\Invite::FARMER, \farm\Invite::CUSTOMER], 'cast' => 'enum'],
			'customer' => ['element32', 'selling\Customer', 'null' => TRUE, 'cast' => 'element'],
			'farmer' => ['element32', 'farm\Farmer', 'null' => TRUE, 'cast' => 'element'],
			'expiresAt' => ['date', 'cast' => 'string'],
			'key' => ['text8', 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\farm\Invite::PENDING, \farm\Invite::ACCEPTED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'email', 'type', 'customer', 'farmer', 'expiresAt', 'key', 'status'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'customer' => 'selling\Customer',
			'farmer' => 'farm\Farmer',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['customer'],
			['email'],
			['key']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'expiresAt' :
				return new \Sql('NOW() + INTERVAL 3 DAY');

			case 'key' :
				return bin2hex(random_bytes(16));

			case 'status' :
				return Invite::PENDING;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): InviteModel {
		return parent::select(...$fields);
	}

	public function where(...$data): InviteModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): InviteModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): InviteModel {
		return $this->where('farm', ...$data);
	}

	public function whereEmail(...$data): InviteModel {
		return $this->where('email', ...$data);
	}

	public function whereType(...$data): InviteModel {
		return $this->where('type', ...$data);
	}

	public function whereCustomer(...$data): InviteModel {
		return $this->where('customer', ...$data);
	}

	public function whereFarmer(...$data): InviteModel {
		return $this->where('farmer', ...$data);
	}

	public function whereExpiresAt(...$data): InviteModel {
		return $this->where('expiresAt', ...$data);
	}

	public function whereKey(...$data): InviteModel {
		return $this->where('key', ...$data);
	}

	public function whereStatus(...$data): InviteModel {
		return $this->where('status', ...$data);
	}


}


abstract class InviteCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Invite {

		$e = new Invite();

		if(empty($id)) {
			Invite::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Invite::getSelection();
		}

		if(Invite::model()
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
			$properties = Invite::getSelection();
		}

		if($sort !== NULL) {
			Invite::model()->sort($sort);
		}

		return Invite::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Invite {

		return new Invite(['id' => NULL]);

	}

	public static function create(Invite $e): void {

		Invite::model()->insert($e);

	}

	public static function update(Invite $e, array $properties): void {

		$e->expects(['id']);

		Invite::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Invite $e, array $properties): void {

		Invite::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Invite $e): void {

		$e->expects(['id']);

		Invite::model()->delete($e);

	}

}


class InvitePage extends \ModulePage {

	protected string $module = 'farm\Invite';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? InviteLib::getPropertiesCreate(),
		   $propertiesUpdate ?? InviteLib::getPropertiesUpdate()
		);
	}

}
?>