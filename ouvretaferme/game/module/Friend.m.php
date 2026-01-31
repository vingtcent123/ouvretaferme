<?php
namespace game;

abstract class FriendElement extends \Element {

	use \FilterElement;

	private static ?FriendModel $model = NULL;

	public static function getSelection(): array {
		return Friend::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): FriendModel {
		if(self::$model === NULL) {
			self::$model = new FriendModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Friend::'.$failName, $arguments, $wrapper);
	}

}


class FriendModel extends \ModuleModel {

	protected string $module = 'game\Friend';
	protected string $package = 'game';
	protected string $table = 'gameFriend';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'friend' => ['element32', 'user\User', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'friend', 'createdAt'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'friend' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'friend'],
			['friend', 'user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): FriendModel {
		return parent::select(...$fields);
	}

	public function where(...$data): FriendModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): FriendModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): FriendModel {
		return $this->where('user', ...$data);
	}

	public function whereFriend(...$data): FriendModel {
		return $this->where('friend', ...$data);
	}

	public function whereCreatedAt(...$data): FriendModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class FriendCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Friend {

		$e = new Friend();

		if(empty($id)) {
			Friend::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Friend::getSelection();
		}

		if(Friend::model()
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
			$properties = Friend::getSelection();
		}

		if($sort !== NULL) {
			Friend::model()->sort($sort);
		}

		return Friend::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Friend {

		return new Friend($properties);

	}

	public static function create(Friend $e): void {

		Friend::model()->insert($e);

	}

	public static function update(Friend $e, array $properties): void {

		$e->expects(['id']);

		Friend::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Friend $e, array $properties): void {

		Friend::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Friend $e): void {

		$e->expects(['id']);

		Friend::model()->delete($e);

	}

}


class FriendPage extends \ModulePage {

	protected string $module = 'game\Friend';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? FriendLib::getPropertiesCreate(),
		   $propertiesUpdate ?? FriendLib::getPropertiesUpdate()
		);
	}

}
?>