<?php
namespace overview;

abstract class VatDeclarationElement extends \Element {

	use \FilterElement;

	private static ?VatDeclarationModel $model = NULL;

	const DRAFT = 'draft';
	const DECLARED = 'declared';
	const DELETED = 'deleted';

	const CA3 = 'ca3';
	const CA12 = 'ca12';

	public static function getSelection(): array {
		return VatDeclaration::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'overview\VatDeclaration';
	protected string $package = 'overview';
	protected string $table = 'overviewVatDeclaration';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'from' => ['date', 'cast' => 'string'],
			'to' => ['date', 'cast' => 'string'],
			'limit' => ['date', 'cast' => 'string'],
			'status' => ['enum', [\overview\VatDeclaration::DRAFT, \overview\VatDeclaration::DECLARED, \overview\VatDeclaration::DELETED], 'cast' => 'enum'],
			'associates' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'cerfa' => ['enum', [\overview\VatDeclaration::CA3, \overview\VatDeclaration::CA12], 'cast' => 'enum'],
			'data' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'financialYear' => ['element32', 'account\FinancialYear', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'updatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'updatedBy' => ['element32', 'user\User', 'cast' => 'element'],
			'declaredAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'declaredBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'accountedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'accountedBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'from', 'to', 'limit', 'status', 'associates', 'cerfa', 'data', 'financialYear', 'createdAt', 'createdBy', 'updatedAt', 'updatedBy', 'declaredAt', 'declaredBy', 'accountedAt', 'accountedBy'
		]);

		$this->propertiesToModule += [
			'financialYear' => 'account\FinancialYear',
			'createdBy' => 'user\User',
			'updatedBy' => 'user\User',
			'declaredBy' => 'user\User',
			'accountedBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['financialYear']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['from', 'to']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'status' :
				return VatDeclaration::DRAFT;

			case 'data' :
				return [];

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			case 'updatedAt' :
				return new \Sql('NOW()');

			case 'updatedBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'cerfa' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'data' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

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

	public function select(...$fields): VatDeclarationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): VatDeclarationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): VatDeclarationModel {
		return $this->where('id', ...$data);
	}

	public function whereFrom(...$data): VatDeclarationModel {
		return $this->where('from', ...$data);
	}

	public function whereTo(...$data): VatDeclarationModel {
		return $this->where('to', ...$data);
	}

	public function whereLimit(...$data): VatDeclarationModel {
		return $this->where('limit', ...$data);
	}

	public function whereStatus(...$data): VatDeclarationModel {
		return $this->where('status', ...$data);
	}

	public function whereAssociates(...$data): VatDeclarationModel {
		return $this->where('associates', ...$data);
	}

	public function whereCerfa(...$data): VatDeclarationModel {
		return $this->where('cerfa', ...$data);
	}

	public function whereData(...$data): VatDeclarationModel {
		return $this->where('data', ...$data);
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

	public function whereUpdatedAt(...$data): VatDeclarationModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereUpdatedBy(...$data): VatDeclarationModel {
		return $this->where('updatedBy', ...$data);
	}

	public function whereDeclaredAt(...$data): VatDeclarationModel {
		return $this->where('declaredAt', ...$data);
	}

	public function whereDeclaredBy(...$data): VatDeclarationModel {
		return $this->where('declaredBy', ...$data);
	}

	public function whereAccountedAt(...$data): VatDeclarationModel {
		return $this->where('accountedAt', ...$data);
	}

	public function whereAccountedBy(...$data): VatDeclarationModel {
		return $this->where('accountedBy', ...$data);
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

	protected string $module = 'overview\VatDeclaration';

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