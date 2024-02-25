<?php
namespace shop;

abstract class RedirectElement extends \Element {

	use \FilterElement;

	private static ?RedirectModel $model = NULL;

	public static function getSelection(): array {
		return Redirect::model()->getProperties();
	}

	public static function model(): RedirectModel {
		if(self::$model === NULL) {
			self::$model = new RedirectModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Redirect::'.$failName, $arguments, $wrapper);
	}

}


class RedirectModel extends \ModuleModel {

	protected string $module = 'shop\Redirect';
	protected string $package = 'shop';
	protected string $table = 'shopRedirect';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'fqn' => ['fqn', 'cast' => 'string'],
			'shop' => ['element32', 'shop\Shop', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'fqn', 'shop', 'createdAt'
		]);

		$this->propertiesToModule += [
			'shop' => 'shop\Shop',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fqn', 'shop']
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

	public function select(...$fields): RedirectModel {
		return parent::select(...$fields);
	}

	public function where(...$data): RedirectModel {
		return parent::where(...$data);
	}

	public function whereFqn(...$data): RedirectModel {
		return $this->where('fqn', ...$data);
	}

	public function whereShop(...$data): RedirectModel {
		return $this->where('shop', ...$data);
	}

	public function whereCreatedAt(...$data): RedirectModel {
		return $this->where('createdAt', ...$data);
	}


}


abstract class RedirectCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Redirect {

		$e = new Redirect();

		if(empty($id)) {
			Redirect::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Redirect::getSelection();
		}

		if(Redirect::model()
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
			$properties = Redirect::getSelection();
		}

		if($sort !== NULL) {
			Redirect::model()->sort($sort);
		}

		return Redirect::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getByFqn(string $fqn, array $properties = []): Redirect {

		$e = new Redirect();

		if(empty($fqn)) {
			Redirect::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Redirect::getSelection();
		}

		if(Redirect::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Redirect::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Redirect::getSelection();
		}

		return Redirect::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCreateElement(): Redirect {

		return new Redirect(['id' => NULL]);

	}

	public static function create(Redirect $e): void {

		Redirect::model()->insert($e);

	}

	public static function update(Redirect $e, array $properties): void {

		$e->expects(['id']);

		Redirect::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Redirect $e, array $properties): void {

		Redirect::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Redirect $e): void {

		$e->expects(['id']);

		Redirect::model()->delete($e);

	}

}


class RedirectPage extends \ModulePage {

	protected string $module = 'shop\Redirect';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? RedirectLib::getPropertiesCreate(),
		   $propertiesUpdate ?? RedirectLib::getPropertiesUpdate()
		);
	}

}
?>