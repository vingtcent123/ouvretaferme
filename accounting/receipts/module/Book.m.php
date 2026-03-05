<?php
namespace receipts;

abstract class BookElement extends \Element {

	use \FilterElement;

	private static ?BookModel $model = NULL;

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

	public static function getSelection(): array {
		return Book::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): BookModel {
		if(self::$model === NULL) {
			self::$model = new BookModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Book::'.$failName, $arguments, $wrapper);
	}

}


class BookModel extends \ModuleModel {

	protected string $module = 'receipts\Book';
	protected string $package = 'receipts';
	protected string $table = 'receiptsBook';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'operations' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'status' => ['enum', [\receipts\Book::ACTIVE, \receipts\Book::INACTIVE], 'cast' => 'enum'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'closedAt' => ['date', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'operations', 'status', 'createdAt', 'closedAt'
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'operations' :
				return 0;

			case 'status' :
				return Book::ACTIVE;

			case 'createdAt' :
				return new \Sql('NOW()');

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

	public function select(...$fields): BookModel {
		return parent::select(...$fields);
	}

	public function where(...$data): BookModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): BookModel {
		return $this->where('id', ...$data);
	}

	public function whereOperations(...$data): BookModel {
		return $this->where('operations', ...$data);
	}

	public function whereStatus(...$data): BookModel {
		return $this->where('status', ...$data);
	}

	public function whereCreatedAt(...$data): BookModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereClosedAt(...$data): BookModel {
		return $this->where('closedAt', ...$data);
	}


}


abstract class BookCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Book {

		$e = new Book();

		if(empty($id)) {
			Book::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Book::getSelection();
		}

		if(Book::model()
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
			$properties = Book::getSelection();
		}

		if($sort !== NULL) {
			Book::model()->sort($sort);
		}

		return Book::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Book {

		return new Book($properties);

	}

	public static function create(Book $e): void {

		Book::model()->insert($e);

	}

	public static function update(Book $e, array $properties): void {

		$e->expects(['id']);

		Book::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Book $e, array $properties): void {

		Book::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Book $e): void {

		$e->expects(['id']);

		Book::model()->delete($e);

	}

}


class BookPage extends \ModulePage {

	protected string $module = 'receipts\Book';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? BookLib::getPropertiesCreate(),
		   $propertiesUpdate ?? BookLib::getPropertiesUpdate()
		);
	}

}
?>