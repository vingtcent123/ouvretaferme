<?php
namespace selling;

class UnitLib extends UnitCrud {

	public static function getPropertiesCreate(): array {
		return ['singular', 'plural', 'short', 'type'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return Unit::model()
			->select(Unit::getSelection())
			->or(
				fn() => $this->whereFarm($eFarm),
				fn() => $this->whereFarm(NULL)
			)
			->sort(new \Sql('fqn IS NULL, id ASC'))
			->getCollection();

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
