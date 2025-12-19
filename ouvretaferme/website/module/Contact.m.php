<?php
namespace website;

abstract class ContactElement extends \Element {

	use \FilterElement;

	private static ?ContactModel $model = NULL;

	public static function getSelection(): array {
		return Contact::model()->getProperties();
	}

	public static function resetModel(): void {
		self::$model = NULL;
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

	protected string $module = 'website\Contact';
	protected string $package = 'website';
	protected string $table = 'websiteContact';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'website' => ['element32', 'website\Website', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'email' => ['email', 'cast' => 'string'],
			'title' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'content' => ['text16', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'website', 'farm', 'name', 'email', 'title', 'content', 'createdAt'
		]);

		$this->propertiesToModule += [
			'website' => 'website\Website',
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm', 'website']
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

	public function select(...$fields): ContactModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ContactModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ContactModel {
		return $this->where('id', ...$data);
	}

	public function whereWebsite(...$data): ContactModel {
		return $this->where('website', ...$data);
	}

	public function whereFarm(...$data): ContactModel {
		return $this->where('farm', ...$data);
	}

	public function whereName(...$data): ContactModel {
		return $this->where('name', ...$data);
	}

	public function whereEmail(...$data): ContactModel {
		return $this->where('email', ...$data);
	}

	public function whereTitle(...$data): ContactModel {
		return $this->where('title', ...$data);
	}

	public function whereContent(...$data): ContactModel {
		return $this->where('content', ...$data);
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

	protected string $module = 'website\Contact';

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