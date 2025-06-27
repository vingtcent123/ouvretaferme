<?php
namespace account;

abstract class PartnerElement extends \Element {

	use \FilterElement;

	private static ?PartnerModel $model = NULL;

	const DROPBOX = 'dropbox';

	public static function getSelection(): array {
		return Partner::model()->getProperties();
	}

	public static function model(): PartnerModel {
		if(self::$model === NULL) {
			self::$model = new PartnerModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Partner::'.$failName, $arguments, $wrapper);
	}

}


class PartnerModel extends \ModuleModel {

	protected string $module = 'account\Partner';
	protected string $package = 'account';
	protected string $table = 'accountPartner';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'partner' => ['enum', [\account\Partner::DROPBOX], 'unique' => TRUE, 'cast' => 'enum'],
			'accessToken' => ['text16', 'cast' => 'string'],
			'params' => ['json', 'null' => TRUE, 'cast' => 'array'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'updatedAt' => ['datetime', 'cast' => 'string'],
			'expiresAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'updatedBy' => ['element32', 'user\User', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'partner', 'accessToken', 'params', 'createdAt', 'updatedAt', 'expiresAt', 'createdBy', 'updatedBy'
		]);

		$this->propertiesToModule += [
			'createdBy' => 'user\User',
			'updatedBy' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['partner']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'params' :
				return [];

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'updatedAt' :
				return new \Sql('NOW()');

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			case 'updatedBy' :
				return \user\ConnectionLib::getOnline();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'partner' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'params' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'params' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): PartnerModel {
		return parent::select(...$fields);
	}

	public function where(...$data): PartnerModel {
		return parent::where(...$data);
	}

	public function wherePartner(...$data): PartnerModel {
		return $this->where('partner', ...$data);
	}

	public function whereAccessToken(...$data): PartnerModel {
		return $this->where('accessToken', ...$data);
	}

	public function whereParams(...$data): PartnerModel {
		return $this->where('params', ...$data);
	}

	public function whereCreatedAt(...$data): PartnerModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereUpdatedAt(...$data): PartnerModel {
		return $this->where('updatedAt', ...$data);
	}

	public function whereExpiresAt(...$data): PartnerModel {
		return $this->where('expiresAt', ...$data);
	}

	public function whereCreatedBy(...$data): PartnerModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereUpdatedBy(...$data): PartnerModel {
		return $this->where('updatedBy', ...$data);
	}


}


abstract class PartnerCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Partner {

		$e = new Partner();

		if(empty($id)) {
			Partner::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Partner::getSelection();
		}

		if(Partner::model()
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
			$properties = Partner::getSelection();
		}

		if($sort !== NULL) {
			Partner::model()->sort($sort);
		}

		return Partner::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Partner {

		return new Partner(['id' => NULL]);

	}

	public static function create(Partner $e): void {

		Partner::model()->insert($e);

	}

	public static function update(Partner $e, array $properties): void {

		$e->expects(['id']);

		Partner::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Partner $e, array $properties): void {

		Partner::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Partner $e): void {

		$e->expects(['id']);

		Partner::model()->delete($e);

	}

}


class PartnerPage extends \ModulePage {

	protected string $module = 'account\Partner';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? PartnerLib::getPropertiesCreate(),
		   $propertiesUpdate ?? PartnerLib::getPropertiesUpdate()
		);
	}

}
?>