<?php
namespace selling;

abstract class PdfElement extends \Element {

	use \FilterElement;

	private static ?PdfModel $model = NULL;

	const DELIVERY_NOTE = 'delivery-note';
	const ORDER_FORM = 'order-form';
	const INVOICE = 'invoice';

	public static function getSelection(): array {
		return Pdf::model()->getProperties();
	}

	public static function model(): PdfModel {
		if(self::$model === NULL) {
			self::$model = new PdfModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Pdf::'.$failName, $arguments, $wrapper);
	}

}


class PdfModel extends \ModuleModel {

	protected string $module = 'selling\Pdf';
	protected string $package = 'selling';
	protected string $table = 'sellingPdf';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'sale' => ['element32', 'selling\Sale', 'cast' => 'element'],
			'used' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'content' => ['element32', 'selling\PdfContent', 'null' => TRUE, 'cast' => 'element'],
			'type' => ['enum', [\selling\Pdf::DELIVERY_NOTE, \selling\Pdf::ORDER_FORM, \selling\Pdf::INVOICE], 'cast' => 'enum'],
			'emailedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'sale', 'used', 'farm', 'content', 'type', 'emailedAt', 'createdAt'
		]);

		$this->propertiesToModule += [
			'sale' => 'selling\Sale',
			'farm' => 'farm\Farm',
			'content' => 'selling\PdfContent',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['content']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['sale', 'type']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'used' :
				return 1;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): PdfModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PdfModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): PdfModel {
		return $this->where('id', ...$data);
	}

	public function whereSale(...$data): PdfModel {
		return $this->where('sale', ...$data);
	}

	public function whereUsed(...$data): PdfModel {
		return $this->where('used', ...$data);
	}

	public function whereFarm(...$data): PdfModel {
		return $this->where('farm', ...$data);
	}

	public function whereContent(...$data): PdfModel {
		return $this->where('content', ...$data);
	}

	public function whereType(...$data): PdfModel {
		return $this->where('type', ...$data);
	}

	public function whereEmailedAt(...$data): PdfModel {
		return $this->where('emailedAt', ...$data);
	}

	public function whereCreatedAt(...$data): PdfModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class PdfCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Pdf {

		$e = new Pdf();

		if(empty($id)) {
			Pdf::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Pdf::getSelection();
		}

		if(Pdf::model()
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
			$properties = Pdf::getSelection();
		}

		if($sort !== NULL) {
			Pdf::model()->sort($sort);
		}

		return Pdf::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Pdf {

		return new Pdf(['id' => NULL]);

	}

	public static function create(Pdf $e): void {

		Pdf::model()->insert($e);

	}

	public static function update(Pdf $e, array $properties): void {

		$e->expects(['id']);

		Pdf::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Pdf $e, array $properties): void {

		Pdf::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Pdf $e): void {

		$e->expects(['id']);

		Pdf::model()->delete($e);

	}

}


class PdfPage extends \ModulePage {

	protected string $module = 'selling\Pdf';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PdfLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PdfLib::getPropertiesUpdate()
		);
	}

}
?>