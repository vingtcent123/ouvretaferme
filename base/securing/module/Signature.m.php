<?php
namespace securing;

abstract class SignatureElement extends \Element {

	use \FilterElement;

	private static ?SignatureModel $model = NULL;

	const SALE = 'sale';
	const CASHBOOK = 'cashbook';

	public static function getSelection(): array {
		return Signature::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): SignatureModel {
		if(self::$model === NULL) {
			self::$model = new SignatureModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Signature::'.$failName, $arguments, $wrapper);
	}

}


class SignatureModel extends \ModuleModel {

	protected string $module = 'securing\Signature';
	protected string $package = 'securing';
	protected string $table = 'securingSignature';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'source' => ['enum', [\securing\Signature::SALE, \securing\Signature::CASHBOOK], 'cast' => 'enum'],
			'key' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'hmac' => ['text8', 'min' => 64, 'max' => 64, 'charset' => 'ascii', 'cast' => 'string'],
			'chained' => ['text8', 'min' => 64, 'max' => 64, 'charset' => 'ascii', 'cast' => 'string'],
			'entry' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'data' => ['json', 'cast' => 'array'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'source', 'key', 'hmac', 'chained', 'entry', 'data', 'createdAt'
		]);

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['hmac']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'source' :
				return ($value === NULL) ? NULL : (string)$value;

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

	public function select(...$fields): SignatureModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SignatureModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SignatureModel {
		return $this->where('id', ...$data);
	}

	public function whereSource(...$data): SignatureModel {
		return $this->where('source', ...$data);
	}

	public function whereKey(...$data): SignatureModel {
		return $this->where('key', ...$data);
	}

	public function whereHmac(...$data): SignatureModel {
		return $this->where('hmac', ...$data);
	}

	public function whereChained(...$data): SignatureModel {
		return $this->where('chained', ...$data);
	}

	public function whereEntry(...$data): SignatureModel {
		return $this->where('entry', ...$data);
	}

	public function whereData(...$data): SignatureModel {
		return $this->where('data', ...$data);
	}

	public function whereCreatedAt(...$data): SignatureModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class SignatureCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Signature {

		$e = new Signature();

		if(empty($id)) {
			Signature::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Signature::getSelection();
		}

		if(Signature::model()
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
			$properties = Signature::getSelection();
		}

		if($sort !== NULL) {
			Signature::model()->sort($sort);
		}

		return Signature::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Signature {

		return new Signature(['id' => NULL]);

	}

	public static function create(Signature $e): void {

		Signature::model()->insert($e);

	}

	public static function update(Signature $e, array $properties): void {

		$e->expects(['id']);

		Signature::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Signature $e, array $properties): void {

		Signature::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Signature $e): void {

		$e->expects(['id']);

		Signature::model()->delete($e);

	}

}


class SignaturePage extends \ModulePage {

	protected string $module = 'securing\Signature';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SignatureLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SignatureLib::getPropertiesUpdate()
		);
	}

}
?>