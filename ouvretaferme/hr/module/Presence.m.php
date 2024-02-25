<?php
namespace hr;

abstract class PresenceElement extends \Element {

	use \FilterElement;

	private static ?PresenceModel $model = NULL;

	public static function getSelection(): array {
		return Presence::model()->getProperties();
	}

	public static function model(): PresenceModel {
		if(self::$model === NULL) {
			self::$model = new PresenceModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Presence::'.$failName, $arguments, $wrapper);
	}

}


class PresenceModel extends \ModuleModel {

	protected string $module = 'hr\Presence';
	protected string $package = 'hr';
	protected string $table = 'hrPresence';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'from' => ['date', 'cast' => 'string'],
			'to' => ['date', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'user', 'from', 'to'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'user' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'user']
		]);

	}

	public function select(...$fields): PresenceModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PresenceModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PresenceModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): PresenceModel {
		return $this->where('farm', ...$data);
	}

	public function whereUser(...$data): PresenceModel {
		return $this->where('user', ...$data);
	}

	public function whereFrom(...$data): PresenceModel {
		return $this->where('from', ...$data);
	}

	public function whereTo(...$data): PresenceModel {
		return $this->where('to', ...$data);
	}


}


abstract class PresenceCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Presence {

		$e = new Presence();

		if(empty($id)) {
			Presence::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Presence::getSelection();
		}

		if(Presence::model()
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
			$properties = Presence::getSelection();
		}

		if($sort !== NULL) {
			Presence::model()->sort($sort);
		}

		return Presence::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Presence {

		return new Presence(['id' => NULL]);

	}

	public static function create(Presence $e): void {

		Presence::model()->insert($e);

	}

	public static function update(Presence $e, array $properties): void {

		$e->expects(['id']);

		Presence::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Presence $e, array $properties): void {

		Presence::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Presence $e): void {

		$e->expects(['id']);

		Presence::model()->delete($e);

	}

}


class PresencePage extends \ModulePage {

	protected string $module = 'hr\Presence';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PresenceLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PresenceLib::getPropertiesUpdate()
		);
	}

}
?>