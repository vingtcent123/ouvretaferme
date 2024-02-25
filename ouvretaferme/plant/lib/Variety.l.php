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

	public static function query(\farm\Farm $eFarm, Plant $ePlant, ?array $properties = []): \Collection {

		// On récupère les variétés déjà utilisées
		$cVarietyUsed = \series\Slice::model()
			->select('variety')
			->whereFarm($eFarm)
			->wherePlant($ePlant)
			->whereVariety('!=', NULL)
			->group('variety')
			->getColumn('variety');

		if($cVarietyUsed->notEmpty()) {
			$use = 'OR id IN ('.implode(', ', $cVarietyUsed->getIds()).')';
		} else {
			$use = '';
		}

		return Variety::model()
			->select($properties ?: Variety::getSelection() + [
				'weight' => new \Sql('IF(plant IS NOT NULL AND (farm = '.Variety::model()->format($eFarm).' '.$use.'), "farm", IF(plant IS NOT NULL, "common", "other"))', 'string')
			])
			->where('farm IS NULL OR farm = '.Variety::model()->format($eFarm).' '.$use)
			->where('plant IS NULL OR plant = '.Variety::model()->format($ePlant))
			->sort(new \Sql('FIELD(weight, "farm", "common", "other"), name ASC'))
			->getCollection(NULL, NULL, ['weight', NULL]);

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
			\production\Slice::model()
				->whereVariety($e)
				->exists()) {
			Variety::fail('deleteUsed');
			return;
		}

		Variety::model()->delete($e);

	}

}
?>
