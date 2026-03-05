<?php
namespace vat;

abstract class DeclarationElement extends \Element {

	use \FilterElement;

	private static ?DeclarationModel $model = NULL;

	const DRAFT = 'draft';
	const DECLARED = 'declared';
	const ACCOUNTED = 'accounted';

	const CA3 = 'ca3';
	const CA12 = 'ca12';

	public static function getSelection(): array {
		return Declaration::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
	}

	public static function model(): DeclarationModel {
		if(self::$model === NULL) {
			self::$model = new DeclarationModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Declaration::'.$failName, $arguments, $wrapper);
	}

}


class DeclarationModel extends \ModuleModel {

	protected string $module = 'vat\Declaration';
	protected string $package = 'vat';
	protected string $table = 'vatDeclaration';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'from' => ['date', 'cast' => 'string'],
			'to' => ['date', 'cast' => 'string'],
			'limit' => ['date', 'cast' => 'string'],
			'status' => ['enum', [\vat\Declaration::DRAFT, \vat\Declaration::DECLARED, \vat\Declaration::ACCOUNTED], 'cast' => 'enum'],
			'associates' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'cerfa' => ['enum', [\vat\Declaration::CA3, \vat\Declaration::CA12], 'cast' => 'enum'],
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
				return Declaration::DRAFT;

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

	public function select(...$fields): DeclarationModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DeclarationModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DeclarationModel {
		return $this->where('id', ...$data);
	}

	public function whereFrom(...$data): DeclarationModel {
		return $this->where('from', ...$data);
	}

	public function whereTo(...$data): DeclarationModel {
		return $this->where('to', ...$data);
	}

	public function whereLimit(...$data): DeclarationModel {
		return $this->where('limit', ...$data);
	}

	public function whereStatus(...$data): DeclarationModel {
		return $this->where('status', ...$data);
	}

	public function whereAssociates(...$data): DeclarationModel {
		return $this->where('associates', ...$data);
	}

	public function whereCerfa(...$data): DeclarationModel {
		return $this->where('cerfa', ...$data);
	}

	public function whereData(...$data): DeclarationModel {
		return $this->where('data', ...$data);
	}

	public function whereFinancialYear(...$data): DeclarationModel {
		return $this->where('financialYear', ...$data);
	}

	public function whereCreatedAt(...$data): DeclarationModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereCreatedBy(...$data): DeclarationModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereUpdatedAt(...$data): DeclarationModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereUpdatedBy(...$data): DeclarationModel {
		return $this->where('updatedBy', ...$data);
	}

	public function whereDeclaredAt(...$data): DeclarationModel {
		return $this->where('declaredAt', ...$data);
	}

	public function whereDeclaredBy(...$data): DeclarationModel {
		return $this->where('declaredBy', ...$data);
	}

	public function whereAccountedAt(...$data): DeclarationModel {
		return $this->where('accountedAt', ...$data);
	}

	public function whereAccountedBy(...$data): DeclarationModel {
		return $this->where('accountedBy', ...$data);
	}


}


abstract class DeclarationCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Declaration {

		$e = new Declaration();

		if(empty($id)) {
			Declaration::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Declaration::getSelection();
		}

		if(Declaration::model()
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
			$properties = Declaration::getSelection();
		}

		if($sort !== NULL) {
			Declaration::model()->sort($sort);
		}

		return Declaration::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getNewElement(array $properties = []): Declaration {

		return new Declaration($properties);

	}

	public static function create(Declaration $e): void {

		Declaration::model()->insert($e);

	}

	public static function update(Declaration $e, array $properties): void {

		$e->expects(['id']);

		Declaration::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Declaration $e, array $properties): void {

		Declaration::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Declaration $e): void {

		$e->expects(['id']);

		Declaration::model()->delete($e);

	}

}


class DeclarationPage extends \ModulePage {

	protected string $module = 'vat\Declaration';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DeclarationLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DeclarationLib::getPropertiesUpdate()
		);
	}

}
?>