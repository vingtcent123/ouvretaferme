<?php
namespace mail;

abstract class ContactElement extends \Element {

	use \FilterElement;

	private static ?ContactModel $model = NULL;

	public static function getSelection(): array {
		return Contact::model()->getProperties();
	}

	public static function model(): ContactModel {
		if(self::$model === NULL) {
			self::$model = new ContactModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Contact::'.$failName, $arguments, $wrapper);
	}

}


class ContactModel extends \ModuleModel {

	protected string $module = 'mail\Contact';
	protected string $package = 'mail';
	protected string $table = 'mailContact';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'email' => ['email', 'cast' => 'string'],
			'lastEmail' => ['element32', 'mail\Email', 'null' => TRUE, 'cast' => 'element'],
			'sent' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'lastSent' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'delivered' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'lastDelivered' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'opened' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'lastOpened' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'failed' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'lastFailed' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'spam' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'lastSpam' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'optIn' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'active' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'email', 'lastEmail', 'sent', 'lastSent', 'delivered', 'lastDelivered', 'opened', 'lastOpened', 'failed', 'lastFailed', 'spam', 'lastSpam', 'optIn', 'active', 'createdAt'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
			'lastEmail' => 'mail\Email',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['email']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['farm', 'email']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'sent' :
				return 0;

			case 'delivered' :
				return 0;

			case 'opened' :
				return 0;

			case 'failed' :
				return 0;

			case 'spam' :
				return 0;

			case 'active' :
				return TRUE;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): ContactModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ContactModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ContactModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): ContactModel {
		return $this->where('farm', ...$data);
	}

	public function whereEmail(...$data): ContactModel {
		return $this->where('email', ...$data);
	}

	public function whereLastEmail(...$data): ContactModel {
		return $this->where('lastEmail', ...$data);
	}

	public function whereSent(...$data): ContactModel {
		return $this->where('sent', ...$data);
	}

	public function whereLastSent(...$data): ContactModel {
		return $this->where('lastSent', ...$data);
	}

	public function whereDelivered(...$data): ContactModel {
		return $this->where('delivered', ...$data);
	}

	public function whereLastDelivered(...$data): ContactModel {
		return $this->where('lastDelivered', ...$data);
	}

	public function whereOpened(...$data): ContactModel {
		return $this->where('opened', ...$data);
	}

	public function whereLastOpened(...$data): ContactModel {
		return $this->where('lastOpened', ...$data);
	}

	public function whereFailed(...$data): ContactModel {
		return $this->where('failed', ...$data);
	}

	public function whereLastFailed(...$data): ContactModel {
		return $this->where('lastFailed', ...$data);
	}

	public function whereSpam(...$data): ContactModel {
		return $this->where('spam', ...$data);
	}

	public function whereLastSpam(...$data): ContactModel {
		return $this->where('lastSpam', ...$data);
	}

	public function whereOptIn(...$data): ContactModel {
		return $this->where('optIn', ...$data);
	}

	public function whereActive(...$data): ContactModel {
		return $this->where('active', ...$data);
	}

	public function whereCreatedAt(...$data): ContactModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class ContactCrud extends \ModuleCrud {

 private static array $cache = [];

	public static function getById(mixed $id, array $properties = []): Contact {

		$e = new Contact();

		if(empty($id)) {
			Contact::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Contact::getSelection();
		}

		if(Contact::model()
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
			$properties = Contact::getSelection();
		}

		if($sort !== NULL) {
			Contact::model()->sort($sort);
		}

		return Contact::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCache(mixed $key, \Closure $callback): mixed {

		self::$cache[$key] ??= $callback();
		return self::$cache[$key];

	}

	public static function getCreateElement(): Contact {

		return new Contact(['id' => NULL]);

	}

	public static function create(Contact $e): void {

		Contact::model()->insert($e);

	}

	public static function update(Contact $e, array $properties): void {

		$e->expects(['id']);

		Contact::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Contact $e, array $properties): void {

		Contact::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Contact $e): void {

		$e->expects(['id']);

		Contact::model()->delete($e);

	}

}


class ContactPage extends \ModulePage {

	protected string $module = 'mail\Contact';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ContactLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ContactLib::getPropertiesUpdate()
		);
	}

}
?>