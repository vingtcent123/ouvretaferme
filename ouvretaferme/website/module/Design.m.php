<?php
namespace website;

abstract class DesignElement extends \Element {

	use \FilterElement;

	private static ?DesignModel $model = NULL;

	public static function getSelection(): array {
		return Design::model()->getProperties();
	}

	public static function model(): DesignModel {
		if(self::$model === NULL) {
			self::$model = new DesignModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Design::'.$failName, $arguments, $wrapper);
	}

}


class DesignModel extends \ModuleModel {

	protected string $module = 'website\Design';
	protected string $package = 'website';
	protected string $table = 'websiteDesign';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'maxWidth' => ['text8', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'maxWidth'
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'maxWidth' :
				return "100%";

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): DesignModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DesignModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DesignModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): DesignModel {
		return $this->where('name', ...$data);
	}

	public function whereMaxWidth(...$data): DesignModel {
		return $this->where('maxWidth', ...$data);
	}


}


abstract class DesignCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Design {

		$e = new Design();

		if(empty($id)) {
			Design::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Design::getSelection();
		}

		if(Design::model()
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
			$properties = Design::getSelection();
		}

		if($sort !== NULL) {
			Design::model()->sort($sort);
		}

		return Design::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Design {

		return new Design(['id' => NULL]);

	}

	public static function create(Design $e): void {

		Design::model()->insert($e);

	}

	public static function update(Design $e, array $properties): void {

		$e->expects(['id']);

		Design::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Design $e, array $properties): void {

		Design::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Design $e): void {

		$e->expects(['id']);

		Design::model()->delete($e);

	}

}


class DesignPage extends \ModulePage {

	protected string $module = 'website\Design';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DesignLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DesignLib::getPropertiesUpdate()
		);
	}

}
?>