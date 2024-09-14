<?php
namespace selling;

abstract class StockBookmarkElement extends \Element {

	use \FilterElement;

	private static ?StockBookmarkModel $model = NULL;

	const KG = 'kg';
	const UNIT = 'unit';
	const BUNCH = 'bunch';

	public static function getSelection(): array {
		return StockBookmark::model()->getProperties();
	}

	public static function model(): StockBookmarkModel {
		if(self::$model === NULL) {
			self::$model = new StockBookmarkModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('StockBookmark::'.$failName, $arguments, $wrapper);
	}

}


class StockBookmarkModel extends \ModuleModel {

	protected string $module = 'selling\StockBookmark';
	protected string $package = 'selling';
	protected string $table = 'sellingStockBookmark';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'product' => ['element32', 'selling\Product', 'cast' => 'element'],
			'plant' => ['element32', 'plant\Plant', 'cast' => 'element'],
			'unit' => ['enum', [\selling\StockBookmark::KG, \selling\StockBookmark::UNIT, \selling\StockBookmark::BUNCH], 'cast' => 'enum'],
			'size' => ['element32', 'plant\Size', 'null' => TRUE, 'cast' => 'element'],
			'variety' => ['element32', 'plant\Variety', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'farm', 'product', 'plant', 'unit', 'size', 'variety', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'product' => 'selling\Product',
			'plant' => 'plant\Plant',
			'size' => 'plant\Size',
			'variety' => 'plant\Variety',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm'],
			['plant', 'unit', 'size', 'variety']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'unit' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): StockBookmarkModel {
		return parent::select(...$fields);
	}

	public function where(...$data): StockBookmarkModel {
		return parent::where(...$data);
	}

	public function whereFarm(...$data): StockBookmarkModel {
		return $this->where('farm', ...$data);
	}

	public function whereProduct(...$data): StockBookmarkModel {
		return $this->where('product', ...$data);
	}

	public function wherePlant(...$data): StockBookmarkModel {
		return $this->where('plant', ...$data);
	}

	public function whereUnit(...$data): StockBookmarkModel {
		return $this->where('unit', ...$data);
	}

	public function whereSize(...$data): StockBookmarkModel {
		return $this->where('size', ...$data);
	}

	public function whereVariety(...$data): StockBookmarkModel {
		return $this->where('variety', ...$data);
	}

	public function whereCreatedAt(...$data): StockBookmarkModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): StockBookmarkModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class StockBookmarkCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): StockBookmark {

		$e = new StockBookmark();

		if(empty($id)) {
			StockBookmark::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = StockBookmark::getSelection();
		}

		if(StockBookmark::model()
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
			$properties = StockBookmark::getSelection();
		}

		if($sort !== NULL) {
			StockBookmark::model()->sort($sort);
		}

		return StockBookmark::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): StockBookmark {

		return new StockBookmark(['id' => NULL]);

	}

	public static function create(StockBookmark $e): void {

		StockBookmark::model()->insert($e);

	}

	public static function update(StockBookmark $e, array $properties): void {

		$e->expects(['id']);

		StockBookmark::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, StockBookmark $e, array $properties): void {

		StockBookmark::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(StockBookmark $e): void {

		$e->expects(['id']);

		StockBookmark::model()->delete($e);

	}

}


class StockBookmarkPage extends \ModulePage {

	protected string $module = 'selling\StockBookmark';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? StockBookmarkLib::getPropertiesCreate(),
		   $propertiesUpdate ?? StockBookmarkLib::getPropertiesUpdate()
		);
	}

}
?>