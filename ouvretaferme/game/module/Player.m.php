<?php
namespace game;

abstract class PlayerElement extends \Element {

	use \FilterElement;

	private static ?PlayerModel $model = NULL;

	public static function getSelection(): array {
		return Player::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): PlayerModel {
		if(self::$model === NULL) {
			self::$model = new PlayerModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Player::'.$failName, $arguments, $wrapper);
	}

}


class PlayerModel extends \ModuleModel {

	protected string $module = 'game\Player';
	protected string $package = 'game';
	protected string $table = 'gamePlayer';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => 20, 'collate' => 'general', 'unique' => TRUE, 'cast' => 'string'],
			'code' => ['text8', 'collate' => 'general', 'null' => TRUE, 'cast' => 'string'],
			'user' => ['element32', 'user\User', 'unique' => TRUE, 'cast' => 'element'],
			'time' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => 0, 'max' => 999999.99, 'cast' => 'float'],
			'timeUpdatedAt' => ['date', 'cast' => 'string'],
			'giftSentAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'giftReceivedAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'points' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'createdAt' => ['date', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'code', 'user', 'time', 'timeUpdatedAt', 'giftSentAt', 'giftReceivedAt', 'points', 'createdAt'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['name'],
			['user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'time' :
				return 0;

			case 'timeUpdatedAt' :
				return new \Sql('CURDATE()');

			case 'points' :
				return 0;

			case 'createdAt' :
				return new \Sql('CURDATE()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): PlayerModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PlayerModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PlayerModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): PlayerModel {
		return $this->where('name', ...$data);
	}

	public function whereCode(...$data): PlayerModel {
		return $this->where('code', ...$data);
	}

	public function whereUser(...$data): PlayerModel {
		return $this->where('user', ...$data);
	}

	public function whereTime(...$data): PlayerModel {
		return $this->where('time', ...$data);
	}

	public function whereTimeUpdatedAt(...$data): PlayerModel {
		return $this->where('timeUpdatedAt', ...$data);
	}

	public function whereGiftSentAt(...$data): PlayerModel {
		return $this->where('giftSentAt', ...$data);
	}

	public function whereGiftReceivedAt(...$data): PlayerModel {
		return $this->where('giftReceivedAt', ...$data);
	}

	public function wherePoints(...$data): PlayerModel {
		return $this->where('points', ...$data);
	}

	public function whereCreatedAt(...$data): PlayerModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class PlayerCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Player {

		$e = new Player();

		if(empty($id)) {
			Player::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Player::getSelection();
		}

		if(Player::model()
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
			$properties = Player::getSelection();
		}

		if($sort !== NULL) {
			Player::model()->sort($sort);
		}

		return Player::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Player {

		return new Player(['id' => NULL]);

	}

	public static function create(Player $e): void {

		Player::model()->insert($e);

	}

	public static function update(Player $e, array $properties): void {

		$e->expects(['id']);

		Player::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Player $e, array $properties): void {

		Player::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Player $e): void {

		$e->expects(['id']);

		Player::model()->delete($e);

	}

}


class PlayerPage extends \ModulePage {

	protected string $module = 'game\Player';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PlayerLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PlayerLib::getPropertiesUpdate()
		);
	}

}
?>