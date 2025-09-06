<?php
namespace plant;

/**
 * Variety basic functions
 */
class VarietyLib extends VarietyCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'supplierSeed', 'supplierPlant', 'weightSeed1000', 'numberPlantKilogram'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function query(\farm\Farm $eFarm, Plant $ePlant): \Collection {

		return Variety::model()
			->select(Variety::getSelection())
			->where('farm IS NULL OR farm = '.Variety::model()->format($eFarm))
			->where('plant IS NULL OR plant = '.Variety::model()->format($ePlant))
			->sort(new \Sql('id != '.PlantSetting::VARIETY_UNKNOWN.', id = '.PlantSetting::VARIETY_MIX.', name ASC'))
			->getCollection();

	}

	public static function getByFarmAndPlant(\farm\Farm $eFarm, Plant $ePlant): \Collection {

		return Variety::model()
			->select(Variety::getSelection())
			->whereFarm($eFarm)
			->wherePlant($ePlant)
			->sort('name')
			->getCollection();

	}

	public static function create(Variety $e): void {

		$e->expects(['farm', 'plant']);

		try {
			parent::create($e);
		} catch(\DuplicateException) {
			Variety::fail('name.duplicate');
		}

	}

	public static function update(Variety $e, array $properties): void {

		try {
			parent::update($e, $properties);
		} catch(\DuplicateException) {
			Variety::fail('name.duplicate');
		}

	}

	public static function delete(Variety $e): void {

		$e->expects(['id']);

		if(\series\Slice::model()
				->whereVariety($e)
				->exists() or
			\sequence\Slice::model()
				->whereVariety($e)
				->exists()) {
			Variety::fail('deleteUsed');
			return;
		}

		Variety::model()->delete($e);

	}

}
?>
