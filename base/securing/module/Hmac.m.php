<?php
namespace securing;

abstract class HmacElement extends \Element {

	use \FilterElement;

	private static ?HmacModel $model = NULL;

	public static function getSelection(): array {
		return Hmac::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): HmacModel {
		if(self::$model === NULL) {
			self::$model = new HmacModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Hmac::'.$failName, $arguments, $wrapper);
	}

}


class HmacModel extends \ModuleModel {

	protected string $module = 'securing\Hmac';
	protected string $package = 'securing';
	protected string $table = 'securingHmac';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'hmac' => ['text8', 'min' => 64, 'max' => 64, 'charset' => 'ascii', 'cast' => 'string'],
			'chained' => ['text8', 'min' => 64, 'max' => 64, 'charset' => 'ascii', 'cast' => 'string'],
			'data' => ['json', 'cast' => 'array'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'hmac', 'chained', 'data', 'createdAt'
		]);

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['hmac']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'data' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'data' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): HmacModel {
		return parent::select(...$fields);
	}

	public function where(...$data): HmacModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): HmacModel {
		return $this->where('id', ...$data);
	}

	public function whereHmac(...$data): HmacModel {
		return $this->where('hmac', ...$data);
	}

	public function whereChained(...$data): HmacModel {
		return $this->where('chained', ...$data);
	}

	public function whereData(...$data): HmacModel {
		return $this->where('data', ...$data);
	}

	public function whereCreatedAt(...$data): HmacModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class HmacCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Hmac {

		$e = new Hmac();

		if(empty($id)) {
			Hmac::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Hmac::getSelection();
		}

		if(Hmac::model()
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
			$properties = Hmac::getSelection();
		}

		if($sort !== NULL) {
			Hmac::model()->sort($sort);
		}

		return Hmac::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Hmac {

		return new Hmac(['id' => NULL]);

	}

	public static function create(Hmac $e): void {

		Hmac::model()->insert($e);

	}

	public static function update(Hmac $e, array $properties): void {

		$e->expects(['id']);

		Hmac::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Hmac $e, array $properties): void {

		Hmac::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Hmac $e): void {

		$e->expects(['id']);

		Hmac::model()->delete($e);

	}

}


class HmacPage extends \ModulePage {

	protected string $module = 'securing\Hmac';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? HmacLib::getPropertiesCreate(),
		   $propertiesUpdate ?? HmacLib::getPropertiesUpdate()
		);
	}

}
?>