<?php
namespace website;

abstract class TemplateElement extends \Element {

	use \FilterElement;

	private static ?TemplateModel $model = NULL;

	public static function getSelection(): array {
		return Template::model()->getProperties();
	}

	public static function model(): TemplateModel {
		if(self::$model === NULL) {
			self::$model = new TemplateModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Template::'.$failName, $arguments, $wrapper);
	}

}


class TemplateModel extends \ModuleModel {

	protected string $module = 'website\Template';
	protected string $package = 'website';
	protected string $table = 'websiteTemplate';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'fqn' => ['fqn', 'unique' => TRUE, 'cast' => 'string'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'description' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'defaultUrl' => ['text8', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'defaultLabel' => ['text8', 'min' => 1, 'max' => NULL, 'null' => TRUE, 'cast' => 'string'],
			'defaultTitle' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'defaultDescription' => ['text8', 'min' => 1, 'max' => 200, 'null' => TRUE, 'cast' => 'string'],
			'defaultContent' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'autocreate' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'fqn', 'name', 'description', 'defaultUrl', 'defaultLabel', 'defaultTitle', 'defaultDescription', 'defaultContent', 'autocreate'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fqn']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'autocreate' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): TemplateModel {
		return parent::select(...$fields);
	}

	public function where(...$data): TemplateModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): TemplateModel {
		return $this->where('id', ...$data);
	}

	public function whereFqn(...$data): TemplateModel {
		return $this->where('fqn', ...$data);
	}

	public function whereName(...$data): TemplateModel {
		return $this->where('name', ...$data);
	}

	public function whereDescription(...$data): TemplateModel {
		return $this->where('description', ...$data);
	}

	public function whereDefaultUrl(...$data): TemplateModel {
		return $this->where('defaultUrl', ...$data);
	}

	public function whereDefaultLabel(...$data): TemplateModel {
		return $this->where('defaultLabel', ...$data);
	}

	public function whereDefaultTitle(...$data): TemplateModel {
		return $this->where('defaultTitle', ...$data);
	}

	public function whereDefaultDescription(...$data): TemplateModel {
		return $this->where('defaultDescription', ...$data);
	}

	public function whereDefaultContent(...$data): TemplateModel {
		return $this->where('defaultContent', ...$data);
	}

	public function whereAutocreate(...$data): TemplateModel {
		return $this->where('autocreate', ...$data);
	}


}


abstract class TemplateCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Template {

		$e = new Template();

		if(empty($id)) {
			Template::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Template::getSelection();
		}

		if(Template::model()
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
			$properties = Template::getSelection();
		}

		if($sort !== NULL) {
			Template::model()->sort($sort);
		}

		return Template::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Template {

		$e = new Template();

		if(empty($fqn)) {
			Template::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Template::getSelection();
		}

		if(Template::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Template::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Template::getSelection();
		}

		return Template::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCreateElement(): Template {

		return new Template(['id' => NULL]);

	}

	public static function create(Template $e): void {

		Template::model()->insert($e);

	}

	public static function update(Template $e, array $properties): void {

		$e->expects(['id']);

		Template::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Template $e, array $properties): void {

		Template::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Template $e): void {

		$e->expects(['id']);

		Template::model()->delete($e);

	}

}


class TemplatePage extends \ModulePage {

	protected string $module = 'website\Template';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? TemplateLib::getPropertiesCreate(),
		   $propertiesUpdate ?? TemplateLib::getPropertiesUpdate()
		);
	}

}
?>