<?php
namespace user;

abstract class BanElement extends \Element {

	use \FilterElement;

	private static ?BanModel $model = NULL;

	public static function getSelection(): array {
		return Ban::model()->getProperties();
	}

	public static function model(): BanModel {
		if(self::$model === NULL) {
			self::$model = new BanModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Ban::'.$failName, $arguments, $wrapper);
	}

}


class BanModel extends \ModuleModel {

	protected string $module = 'user\Ban';
	protected string $package = 'user';
	protected string $table = 'userBan';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'ip' => ['ipv4', 'null' => TRUE, 'cast' => 'string'],
			'reason' => ['text8', 'min' => 0, 'max' => NULL, 'cast' => 'string'],
			'admin' => ['element32', 'user\User', 'cast' => 'element'],
			'since' => ['datetime', 'cast' => 'string'],
			'until' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'ip', 'reason', 'admin', 'since', 'until'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'admin' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['user'],
			['ip']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'user' :
				return NULL;

			case 'ip' :
				return NULL;

			case 'since' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'ip' :
				return $value === NULL ? NULL : (int)first(unpack('l', pack('l', ip2long($value))));

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'ip' :
				return $value === NULL ? NULL : long2ip($value);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): BanModel {
		return parent::select(...$fields);
	}

	public function where(...$data): BanModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): BanModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): BanModel {
		return $this->where('user', ...$data);
	}

	public function whereIp(...$data): BanModel {
		return $this->where('ip', ...$data);
	}

	public function whereReason(...$data): BanModel {
		return $this->where('reason', ...$data);
	}

	public function whereAdmin(...$data): BanModel {
		return $this->where('admin', ...$data);
	}

	public function whereSince(...$data): BanModel {
		return $this->where('since', ...$data);
	}

	public function whereUntil(...$data): BanModel {
		return $this->where('until', ...$data);
	}


}


abstract class BanCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Ban {

		$e = new Ban();

		if(empty($id)) {
			Ban::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Ban::getSelection();
		}

		if(Ban::model()
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
			$properties = Ban::getSelection();
		}

		if($sort !== NULL) {
			Ban::model()->sort($sort);
		}

		return Ban::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Ban {

		return new Ban(['id' => NULL]);

	}

	public static function create(Ban $e): void {

		Ban::model()->insert($e);

	}

	public static function update(Ban $e, array $properties): void {

		$e->expects(['id']);

		Ban::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Ban $e, array $properties): void {

		Ban::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Ban $e): void {

		$e->expects(['id']);

		Ban::model()->delete($e);

	}

}


class BanPage extends \ModulePage {

	protected string $module = 'user\Ban';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? BanLib::getPropertiesCreate(),
		   $propertiesUpdate ?? BanLib::getPropertiesUpdate()
		);
	}

}
?>