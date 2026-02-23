<?php
namespace selling;

class UnitLib extends UnitCrud {

	public static function getPropertiesCreate(): array {
		return ['singular', 'plural', 'short', 'type', 'approximate'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function duplicateForFarm(\farm\Farm $eFarm): void {

		$cUnit = Unit::model()
			->select(Unit::getSelection())
			->whereFarm(NULL)
			->getCollection();

		$cUnit->map(function(Unit $eUnit) use($eFarm) {
			$eUnit['id'] = NULL;
			$eUnit['farm'] = $eFarm;
		});

		Unit::model()->insert($cUnit);

	}

	public static function getByFarm(\farm\Farm $eFarm, string|\Sql|null $sort = 'singular', ?string $index = NULL): \Collection {

		return Unit::model()
			->select(Unit::getSelection())
			->whereFarm($eFarm)
			->sort($sort)
			->getCollection(index: $index);

	}

	public static function getByFarmWithoutWeight(\farm\Farm $eFarm): \Collection {

		Unit::model()->whereApproximate(FALSE);

		return self::getByFarm($eFarm);

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
