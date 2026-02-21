<?php
namespace invoicing;

abstract class LineElement extends \Element {

	use \FilterElement;

	private static ?LineModel $model = NULL;

	const GOOD = 'good';
	const SERVICE = 'service';

	public static function getSelection(): array {
		return Line::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): LineModel {
		if(self::$model === NULL) {
			self::$model = new LineModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Line::'.$failName, $arguments, $wrapper);
	}

}


class LineModel extends \ModuleModel {

	protected string $module = 'invoicing\Line';
	protected string $package = 'invoicing';
	protected string $table = 'invoicingLine';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'invoice' => ['element32', 'invoicing\Invoice', 'cast' => 'element'],
			'identifier' => ['text8', 'cast' => 'string'],
			'name' => ['text8', 'cast' => 'string'],
			'unitPrice' => ['decimal', 'digits' => 12, 'decimal' => 6, 'min' => -999999.999999, 'max' => 999999.999999, 'null' => TRUE, 'cast' => 'float'],
			'price' => ['decimal', 'digits' => 8, 'decimal' => 2, 'min' => -999999.99, 'max' => 999999.99, 'null' => TRUE, 'cast' => 'float'],
			'vatRate' => ['decimal', 'digits' => 4, 'decimal' => 2, 'min' => 0.0, 'max' => 99.99, 'null' => TRUE, 'cast' => 'float'],
			'vatCode' => ['text8', 'charset' => 'ascii', 'cast' => 'string'],
			'quantity' => ['decimal', 'digits' => 10, 'decimal' => 4, 'min' => -999999.9999, 'max' => 999999.9999, 'null' => TRUE, 'cast' => 'float'],
			'quantityCode' => ['text8', 'charset' => 'ascii', 'cast' => 'string'],
			'nature' => ['enum', [\invoicing\Line::GOOD, \invoicing\Line::SERVICE], 'cast' => 'enum'],
			'account' => ['element32', 'account\Account', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'invoice', 'identifier', 'name', 'unitPrice', 'price', 'vatRate', 'vatCode', 'quantity', 'quantityCode', 'nature', 'account'
		]);

		$this->propertiesToModule += [
			'invoice' => 'invoicing\Invoice',
			'account' => 'account\Account',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['invoice']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'nature' :
				return Line::GOOD;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'nature' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): LineModel {
		return parent::select(...$fields);
	}

	public function where(...$data): LineModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): LineModel {
		return $this->where('id', ...$data);
	}

	public function whereInvoice(...$data): LineModel {
		return $this->where('invoice', ...$data);
	}

	public function whereIdentifier(...$data): LineModel {
		return $this->where('identifier', ...$data);
	}

	public function whereName(...$data): LineModel {
		return $this->where('name', ...$data);
	}

	public function whereUnitPrice(...$data): LineModel {
		return $this->where('unitPrice', ...$data);
	}

	public function wherePrice(...$data): LineModel {
		return $this->where('price', ...$data);
	}

	public function whereVatRate(...$data): LineModel {
		return $this->where('vatRate', ...$data);
	}

	public function whereVatCode(...$data): LineModel {
		return $this->where('vatCode', ...$data);
	}

	public function whereQuantity(...$data): LineModel {
		return $this->where('quantity', ...$data);
	}

	public function whereQuantityCode(...$data): LineModel {
		return $this->where('quantityCode', ...$data);
	}

	public function whereNature(...$data): LineModel {
		return $this->where('nature', ...$data);
	}

	public function whereAccount(...$data): LineModel {
		return $this->where('account', ...$data);
	}


}


abstract class LineCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Line {

		$e = new Line();

		if(empty($id)) {
			Line::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Line::getSelection();
		}

		if(Line::model()
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
			$properties = Line::getSelection();
		}

		if($sort !== NULL) {
			Line::model()->sort($sort);
		}

		return Line::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Line {

		return new Line($properties);

	}

	public static function create(Line $e): void {

		Line::model()->insert($e);

	}

	public static function update(Line $e, array $properties): void {

		$e->expects(['id']);

		Line::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Line $e, array $properties): void {

		Line::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Line $e): void {

		$e->expects(['id']);

		Line::model()->delete($e);

	}

}


class LinePage extends \ModulePage {

	protected string $module = 'invoicing\Line';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? LineLib::getPropertiesCreate(),
		   $propertiesUpdate ?? LineLib::getPropertiesUpdate()
		);
	}

}
?>