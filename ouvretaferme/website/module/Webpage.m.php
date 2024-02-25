<?php
namespace website;

abstract class WebpageElement extends \Element {

	use \FilterElement;

	private static ?WebpageModel $model = NULL;

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

	public static function getSelection(): array {
		return Webpage::model()->getProperties();
	}

	public static function model(): WebpageModel {
		if(self::$model === NULL) {
			self::$model = new WebpageModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Webpage::'.$failName, $arguments, $wrapper);
	}

}


class WebpageModel extends \ModuleModel {

	protected string $module = 'website\Webpage';
	protected string $package = 'website';
	protected string $table = 'websiteWebpage';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'title' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'website' => ['element32', 'website\Website', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'template' => ['element32', 'website\Template', 'cast' => 'element'],
			'url' => ['text8', 'min' => 0, 'max' => 50, 'cast' => 'string'],
			'description' => ['text8', 'min' => 1, 'max' => 200, 'null' => TRUE, 'cast' => 'string'],
			'content' => ['editor24', 'null' => TRUE, 'cast' => 'string'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'status' => ['enum', [\website\Webpage::ACTIVE, \website\Webpage::INACTIVE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'title', 'website', 'farm', 'template', 'url', 'description', 'content', 'createdAt', 'status'
		]);

		$this->propertiesToModule += [
			'website' => 'website\Website',
			'farm' => 'farm\Farm',
			'template' => 'website\Template',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['website']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['website', 'url']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Webpage::INACTIVE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): WebpageModel {
		return parent::select(...$fields);
	}

	public function where(...$data): WebpageModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): WebpageModel {
		return $this->where('id', ...$data);
	}

	public function whereTitle(...$data): WebpageModel {
		return $this->where('title', ...$data);
	}

	public function whereWebsite(...$data): WebpageModel {
		return $this->where('website', ...$data);
	}

	public function whereFarm(...$data): WebpageModel {
		return $this->where('farm', ...$data);
	}

	public function whereTemplate(...$data): WebpageModel {
		return $this->where('template', ...$data);
	}

	public function whereUrl(...$data): WebpageModel {
		return $this->where('url', ...$data);
	}

	public function whereDescription(...$data): WebpageModel {
		return $this->where('description', ...$data);
	}

	public function whereContent(...$data): WebpageModel {
		return $this->where('content', ...$data);
	}

	public function whereCreatedAt(...$data): WebpageModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereStatus(...$data): WebpageModel {
		return $this->where('status', ...$data);
	}


}


abstract class WebpageCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Webpage {

		$e = new Webpage();

		if(empty($id)) {
			Webpage::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Webpage::getSelection();
		}

		if(Webpage::model()
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
			$properties = Webpage::getSelection();
		}

		if($sort !== NULL) {
			Webpage::model()->sort($sort);
		}

		return Webpage::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Webpage {

		return new Webpage(['id' => NULL]);

	}

	public static function create(Webpage $e): void {

		Webpage::model()->insert($e);

	}

	public static function update(Webpage $e, array $properties): void {

		$e->expects(['id']);

		Webpage::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Webpage $e, array $properties): void {

		Webpage::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Webpage $e): void {

		$e->expects(['id']);

		Webpage::model()->delete($e);

	}

}


class WebpagePage extends \ModulePage {

	protected string $module = 'website\Webpage';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? WebpageLib::getPropertiesCreate(),
		   $propertiesUpdate ?? WebpageLib::getPropertiesUpdate()
		);
	}

}
?>