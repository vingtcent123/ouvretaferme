<?php
namespace game;

abstract class TileElement extends \Element {

	use \FilterElement;

	private static ?TileModel $model = NULL;

	public static function getSelection(): array {
		return Tile::model()->getProperties();
	}

	public static function model(): TileModel {
		if(self::$model === NULL) {
			self::$model = new TileModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Tile::'.$failName, $arguments, $wrapper);
	}

}


class TileModel extends \ModuleModel {

	protected string $module = 'game\Tile';
	protected string $package = 'game';
	protected string $table = 'gameTile';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'board' => ['int8', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'position' => ['int8', 'min' => 1, 'max' => NULL, 'cast' => 'int'],
			'growing' => ['element32', 'game\Growing', 'cast' => 'element'],
			'growingBefore' => ['element32', 'game\Growing', 'null' => TRUE, 'cast' => 'element'],
			'harvest' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'watered' => ['bool', 'cast' => 'bool'],
			'plantedAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
			'harvestedAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'user', 'board', 'position', 'growing', 'growingBefore', 'harvest', 'watered', 'plantedAt', 'harvestedAt'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'growing' => 'game\Growing',
			'growingBefore' => 'game\Growing',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'board', 'position']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'watered' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): TileModel {
		return parent::select(...$fields);
	}

	public function where(...$data): TileModel {
		return parent::where(...$data);
	}

	public function whereUser(...$data): TileModel {
		return $this->where('user', ...$data);
	}

	public function whereBoard(...$data): TileModel {
		return $this->where('board', ...$data);
	}

	public function wherePosition(...$data): TileModel {
		return $this->where('position', ...$data);
	}

	public function whereGrowing(...$data): TileModel {
		return $this->where('growing', ...$data);
	}

	public function whereGrowingBefore(...$data): TileModel {
		return $this->where('growingBefore', ...$data);
	}

	public function whereHarvest(...$data): TileModel {
		return $this->where('harvest', ...$data);
	}

	public function whereWatered(...$data): TileModel {
		return $this->where('watered', ...$data);
	}

	public function wherePlantedAt(...$data): TileModel {
		return $this->where('plantedAt', ...$data);
	}

	public function whereHarvestedAt(...$data): TileModel {
		return $this->where('harvestedAt', ...$data);
	}


}


abstract class TileCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Tile {

		$e = new Tile();

		if(empty($id)) {
			Tile::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Tile::getSelection();
		}

		if(Tile::model()
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
			$properties = Tile::getSelection();
		}

		if($sort !== NULL) {
			Tile::model()->sort($sort);
		}

		return Tile::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Tile {

		return new Tile(['id' => NULL]);

	}

	public static function create(Tile $e): void {

		Tile::model()->insert($e);

	}

	public static function update(Tile $e, array $properties): void {

		$e->expects(['id']);

		Tile::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Tile $e, array $properties): void {

		Tile::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Tile $e): void {

		$e->expects(['id']);

		Tile::model()->delete($e);

	}

}


class TilePage extends \ModulePage {

	protected string $module = 'game\Tile';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? TileLib::getPropertiesCreate(),
		   $propertiesUpdate ?? TileLib::getPropertiesUpdate()
		);
	}

}
?>