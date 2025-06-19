<?php
namespace plant;

/**
 * Size basic functions
 */
class SizeLib extends SizeCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'comment', 'yield'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'comment', 'yield'];
	}

	public static function getByFarmAndPlant(\farm\Farm $eFarm, Plant $ePlant): \Collection {

		return Size::model()
			->select(Size::getSelection())
			->whereFarm($eFarm)
			->wherePlant($ePlant)
			->sort('name')
			->getCollection();

	}

	public static function getForYield(\farm\Farm $eFarm, Plant $ePlant): \Collection {

		return Size::model()
			->select('id')
			->whereFarm($eFarm)
			->wherePlant($ePlant)
			->whereYield(TRUE)
			->getCollection();

	}

	public static function create(Size $e): void {

		$e->expects(['farm', 'plant']);

		try {
			parent::create($e);
		} catch(\DuplicateException) {
			Size::fail('name.duplicate');
		}

	}

	public static function update(Size $e, array $properties): void {

		try {
			parent::update($e, $properties);
		} catch(\DuplicateException) {
			Size::fail('name.duplicate');
		}

	}

	public static function delete(Size $e): void {

		$e->expects(['id']);

		if(\series\Task::model()
				->whereHarvestSize($e)
				->exists()) {
			Size::fail('deleteUsed');
			return;
		}

		Size::model()->delete($e);

	}

}
?>
