<?php
namespace plant;

/**
 * Quality basic functions
 */
class QualityLib extends QualityCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'comment', 'yield'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'comment', 'yield'];
	}

	public static function getByFarmAndPlant(\farm\Farm $eFarm, Plant $ePlant): \Collection {

		return Quality::model()
			->select(Quality::getSelection())
			->whereFarm($eFarm)
			->wherePlant($ePlant)
			->sort('name')
			->getCollection();

	}

	public static function getForYield(\farm\Farm $eFarm, Plant $ePlant): \Collection {

		return Quality::model()
			->select('id')
			->whereFarm($eFarm)
			->wherePlant($ePlant)
			->whereYield(TRUE)
			->getCollection();

	}

	public static function create(Quality $e): void {

		$e->expects(['farm', 'plant']);

		try {
			parent::create($e);
		} catch(\DuplicateException $e) {
			Quality::fail('name.duplicate');
		}

	}

	public static function update(Quality $e, array $properties): void {

		try {
			parent::update($e, $properties);
		} catch(\DuplicateException $e) {
			Quality::fail('name.duplicate');
		}

	}

	public static function delete(Quality $e): void {

		$e->expects(['id']);

		if(\series\Task::model()
				->whereHarvestQuality($e)
				->exists()) {
			Quality::fail('deleteUsed');
			return;
		}

		Quality::model()->delete($e);

	}

}
?>
