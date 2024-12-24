<?php
namespace selling;

class UnitLib extends UnitCrud {

	public static function getPropertiesCreate(): array {
		return ['singular', 'plural', 'short', 'type'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function getByFarm(\farm\Farm $eFarm, string|\Sql|null $sort = 'singular'): \Collection {

		return Unit::model()
			->select(Unit::getSelection())
			->or(
				fn() => $this->whereFarm($eFarm),
				fn() => $this->whereFarm(NULL)
			)
			->sort($sort)
			->getCollection();

	}

	public static function create(Unit $e): void {

		try {
			parent::create($e);
		} catch(\DuplicateException) {
			Unit::fail('singular.duplicate');
		}

	}

	public static function update(Unit $e, array $properties): void {

		try {
			parent::update($e, $properties);
		} catch(\DuplicateException) {
			Unit::fail('singular.duplicate');
		}

	}

	public static function delete(Unit $e): void {

		$e->expects(['id', 'farm']);

		if(
			\selling\Item::model()
				->whereFarm($e['farm'])
				->whereUnit($e)
				->exists() or
			\selling\Product::model()
				->whereFarm($e['farm'])
				->whereUnit($e)
				->exists()) {
			Unit::fail('deleteUsed');
			return;
		}

		parent::delete($e);

	}

}
?>
