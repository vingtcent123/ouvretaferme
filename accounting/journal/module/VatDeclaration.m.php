<?php
namespace journal;

abstract class VatDeclarationElement extends \Element {

	use \FilterElement;

	private static ?VatDeclarationModel $model = NULL;

	const STATEMENT = 'statement';
	const AMENDMENT = 'amendment';

	public static function getSelection(): array {
		return VatDeclaration::model()->getProperties();
	}

	public static function model(): VatDeclarationModel {
		if(self::$model === NULL) {
			self::$model = new VatDeclarationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('VatDeclaration::'.$failName, $arguments, $wrapper);
	}

}


class VatDeclarationModel extends \ModuleModel {

	protected string $module = 'journal\VatDeclaration';
	protected string $package = 'journal';
	protected string $table = 'journalVatDeclaration';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'startDate' => ['date', 'cast' => 'string'],
			'endDate' => ['date', 'cast' => 'string'],
			'type' => ['enum', [\journal\VatDeclaration::STATEMENT, \journal\VatDeclaration::AMENDMENT], 'cast' => 'enum'],
			'amendment' => ['element32', 'journal\VatDeclaration', 'null' => TRUE, 'cast' => 'element'],
			'collectedVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'deductibleVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'dueVat' => ['decimal', 'digits' => 8, 'decimal' => 2, 'cast' => 'float'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'startDate', 'endDate', 'type', 'amendment', 'collectedVat', 'deductibleVat', 'dueVat', 'financialYear', 'createdAt', 'createdBy'
		]);

		$this->propertiesToModule += [
			'amendment' => 'journal\VatDeclaration',
			'financialYear' => 'account\FinancialYear',
			'createdBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['financialYear']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'type' :
				return VatDeclaration::STATEMENT;

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

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): VatDeclarationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): VatDeclarationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): VatDeclarationModel {
		return $this->where('id', ...$data);
	}

	public function whereStartDate(...$data): VatDeclarationModel {
		return $this->where('startDate', ...$data);
	}

	public function whereEndDate(...$data): VatDeclarationModel {
		return $this->where('endDate', ...$data);
	}

	public function whereType(...$data): VatDeclarationModel {
		return $this->where('type', ...$data);
	}

	public function whereAmendment(...$data): VatDeclarationModel {
		return $this->where('amendment', ...$data);
	}

	public function whereCollectedVat(...$data): VatDeclarationModel {
		return $this->where('collectedVat', ...$data);
	}

	public function whereDeductibleVat(...$data): VatDeclarationModel {
		return $this->where('deductibleVat', ...$data);
	}

	public function whereDueVat(...$data): VatDeclarationModel {
		return $this->where('dueVat', ...$data);
	}

	public function whereFinancialYear(...$data): VatDeclarationModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereCreatedAt(...$data): VatDeclarationModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): VatDeclarationModel {
		return $this->where('createdBy', ...$data);
	}


}


abstract class VatDeclarationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): VatDeclaration {

		$e = new VatDeclaration();

		if(empty($id)) {
			VatDeclaration::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = VatDeclaration::getSelection();
		}

		if(VatDeclaration::model()
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
			$properties = VatDeclaration::getSelection();
		}

		if($sort !== NULL) {
			VatDeclaration::model()->sort($sort);
		}

		return VatDeclaration::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): VatDeclaration {

		return new VatDeclaration(['id' => NULL]);

	}

	public static function create(VatDeclaration $e): void {

		VatDeclaration::model()->insert($e);

	}

	public static function update(VatDeclaration $e, array $properties): void {

		$e->expects(['id']);

		VatDeclaration::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, VatDeclaration $e, array $properties): void {

		VatDeclaration::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(VatDeclaration $e): void {

		$e->expects(['id']);

		VatDeclaration::model()->delete($e);

	}

}


class VatDeclarationPage extends \ModulePage {

	protected string $module = 'journal\VatDeclaration';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? VatDeclarationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? VatDeclarationLib::getPropertiesUpdate()
		);
	}

}
?>