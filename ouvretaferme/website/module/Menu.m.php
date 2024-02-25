<?php
namespace website;

abstract class MenuElement extends \Element {

	use \FilterElement;

	private static ?MenuModel $model = NULL;

	const ACTIVE = 'active';
	const INACTIVE = 'inactive';

	public static function getSelection(): array {
		return Menu::model()->getProperties();
	}

	public static function model(): MenuModel {
		if(self::$model === NULL) {
			self::$model = new MenuModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Menu::'.$failName, $arguments, $wrapper);
	}

}


class MenuModel extends \ModuleModel {

	protected string $module = 'website\Menu';
	protected string $package = 'website';
	protected string $table = 'websiteMenu';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'website' => ['element32', 'website\Website', 'cast' => 'element'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'url' => ['url', 'null' => TRUE, 'cast' => 'string'],
			'webpage' => ['element32', 'website\Webpage', 'null' => TRUE, 'unique' => TRUE, 'cast' => 'element'],
			'label' => ['text8', 'min' => 1, 'max' => 50, 'cast' => 'string'],
			'position' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'status' => ['enum', [\website\Menu::ACTIVE, \website\Menu::INACTIVE], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'website', 'farm', 'url', 'webpage', 'label', 'position', 'status'
		]);

		$this->propertiesToModule += [
			'website' => 'website\Website',
			'farm' => 'farm\Farm',
			'webpage' => 'website\Webpage',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['website']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['webpage']
		]);

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): MenuModel {
		return parent::select(...$fields);
	}

	public function where(...$data): MenuModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): MenuModel {
		return $this->where('id', ...$data);
	}

	public function whereWebsite(...$data): MenuModel {
		return $this->where('website', ...$data);
	}

	public function whereFarm(...$data): MenuModel {
		return $this->where('farm', ...$data);
	}

	public function whereUrl(...$data): MenuModel {
		return $this->where('url', ...$data);
	}

	public function whereWebpage(...$data): MenuModel {
		return $this->where('webpage', ...$data);
	}

	public function whereLabel(...$data): MenuModel {
		return $this->where('label', ...$data);
	}

	public function wherePosition(...$data): MenuModel {
		return $this->where('position', ...$data);
	}

	public function whereStatus(...$data): MenuModel {
		return $this->where('status', ...$data);
	}


}


abstract class MenuCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Menu {

		$e = new Menu();

		if(empty($id)) {
			Menu::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Menu::getSelection();
		}

		if(Menu::model()
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
			$properties = Menu::getSelection();
		}

		if($sort !== NULL) {
			Menu::model()->sort($sort);
		}

		return Menu::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Menu {

		return new Menu(['id' => NULL]);

	}

	public static function create(Menu $e): void {

		Menu::model()->insert($e);

	}

	public static function update(Menu $e, array $properties): void {

		$e->expects(['id']);

		Menu::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Menu $e, array $properties): void {

		Menu::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Menu $e): void {

		$e->expects(['id']);

		Menu::model()->delete($e);

	}

}


class MenuPage extends \ModulePage {

	protected string $module = 'website\Menu';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? MenuLib::getPropertiesCreate(),
		   $propertiesUpdate ?? MenuLib::getPropertiesUpdate()
		);
	}

}
?>