<?php
namespace journal;

abstract class LetteringElement extends \Element {

	use \FilterElement;

	private static ?LetteringModel $model = NULL;

	public static function getSelection(): array {
		return Lettering::model()->getProperties();
	}

	public static function model(): LetteringModel {
		if(self::$model === NULL) {
			self::$model = new LetteringModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Lettering::'.$failName, $arguments, $wrapper);
	}

}


class LetteringModel extends \ModuleModel {

	protected string $module = 'journal\Lettering';
	protected string $package = 'journal';
	protected string $table = 'journalLettering';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'credit' => ['element32', 'journal\Operation', 'cast' => 'element'],
			'debit' => ['element32', 'journal\Operation', 'cast' => 'element'],
			'code' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'amount' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'credit', 'debit', 'code', 'amount', 'createdAt'
		]);

		$this->propertiesToModule += [
			'credit' => 'journal\Operation',
			'debit' => 'journal\Operation',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['credit'],
			['debit']
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

	public function select(...$fields): LetteringModel {
		return parent::select(...$fields);
	}

	public function where(...$data): LetteringModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): LetteringModel {
		return $this->where('id', ...$data);
	}

	public function whereCredit(...$data): LetteringModel {
		return $this->where('credit', ...$data);
	}

	public function whereDebit(...$data): LetteringModel {
		return $this->where('debit', ...$data);
	}

	public function whereCode(...$data): LetteringModel {
		return $this->where('code', ...$data);
	}

	public function whereAmount(...$data): LetteringModel {
		return $this->where('amount', ...$data);
	}

	public function whereCreatedAt(...$data): LetteringModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class LetteringCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Lettering {

		$e = new Lettering();

		if(empty($id)) {
			Lettering::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Lettering::getSelection();
		}

		if(Lettering::model()
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
			$properties = Lettering::getSelection();
		}

		if($sort !== NULL) {
			Lettering::model()->sort($sort);
		}

		return Lettering::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Lettering {

		return new Lettering(['id' => NULL]);

	}

	public static function create(Lettering $e): void {

		Lettering::model()->insert($e);

	}

	public static function update(Lettering $e, array $properties): void {

		$e->expects(['id']);

		Lettering::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Lettering $e, array $properties): void {

		Lettering::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Lettering $e): void {

		$e->expects(['id']);

		Lettering::model()->delete($e);

	}

}


class LetteringPage extends \ModulePage {

	protected string $module = 'journal\Lettering';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? LetteringLib::getPropertiesCreate(),
		   $propertiesUpdate ?? LetteringLib::getPropertiesUpdate()
		);
	}

}
?>