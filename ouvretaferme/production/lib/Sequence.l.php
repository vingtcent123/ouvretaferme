<?php
namespace production;

class SequenceLib extends SequenceCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'cycle', 'perennialLifetime', 'summary', 'description', 'use', 'bedWidth', 'alleyWidth', 'plantsList'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'summary', 'description', 'use', 'bedWidth', 'alleyWidth', 'perennialLifetime', 'mode', 'visibility'];
	}

	public static function countByFarm(\farm\Farm $eFarm): array {

		return Sequence::model()
			->select([
				Sequence::ACTIVE => new \Sql('SUM(status = "'.Sequence::ACTIVE.'")', 'int'),
				Sequence::CLOSED => new \Sql('SUM(status = "'.Sequence::CLOSED.'")', 'int')
			])
			->whereFarm($eFarm)
			->get()
			->getArrayCopy() ?: [Sequence::ACTIVE => 0, Sequence::CLOSED => 0];

	}

	public static function getForSeries(\series\Series $eSeries, \Collection $cCultivation): \Collection {

		$eSeries->expects(['id']);

		$plants = $cCultivation->getColumnCollection('plant')->getIds();
		sort($plants);

		if($plants === []) {
			return new \Collection();
		}

		return Sequence::model()
			->join(Crop::model(), 'm1.id = m2.sequence')
			->select(Sequence::getSelection())
			->where('m1.farm', $eSeries['farm'])
			->where('m1.status', Sequence::ACTIVE)
			->having(new \Sql('GROUP_CONCAT(DISTINCT m2.plant ORDER BY m2.plant) = \''.implode(',', $plants).'\''))
			->group('m1.id')
			->getCollection(NULL, NULL, 'id');

	}

	public static function getCreateElement(): Sequence {
		return new Sequence([
			'id' => NULL,
			'cycle' => Sequence::ANNUAL
		]);
	}

	public static function create(Sequence $e): void {

		$e->expects(['cPlant', 'cycle']);

		Sequence::model()->beginTransaction();

		$e['plants'] = count($e['cPlant']);

		Sequence::model()->insert($e);

		$cCrop = new \Collection();

		foreach($e['cPlant'] as $ePlant) {
			$cCrop[] = new Crop([
				'plant' => $ePlant,
				'farm' => $e['farm'],
				'sequence' => $e
			]);
		}

		Crop::model()->insert($cCrop);

		Sequence::model()->commit();

	}

	/**
	 * Dupliquer une série
	 */
	public static function duplicate(Sequence $eSequence): Sequence {

		$properties = ['name', 'summary', 'description', 'cycle', 'perennialLifetime', 'farm', 'plants', 'use', 'bedWidth', 'alleyWidth', 'mode', 'comment'];
		$eSequence->expects($properties);

		Sequence::model()->beginTransaction();

		// Créer une nouvelle série
		$eSequenceNew = new Sequence($eSequence->extracts($properties));
		$eSequenceNew['name'] = (new SequenceUi())->getDuplicateName($eSequenceNew);
		$eSequenceNew['duplicateOf'] = $eSequence;
		$eSequenceNew['status'] = Sequence::ACTIVE;

		Sequence::model()->insert($eSequenceNew);

		// Dupliquer les cultures et les variétés
		$cCrop = self::duplicateCrops($eSequence, $eSequenceNew);

		// Dupliquer les tâches
		$cFlow = FlowLib::duplicateFromSequence($eSequence, $eSequenceNew, $cCrop);

		// Dupliquer les variétés
		self::duplicateSlices($eSequence, $eSequenceNew, $cCrop);

		// Dupliquer les outils
		self::duplicateRequirements($eSequence, $eSequenceNew, $cCrop, $cFlow);

		Sequence::model()->commit();

		return $eSequenceNew;

	}

	private static function duplicateCrops(Sequence $eSequence, Sequence $eSequenceNew): \Collection {

		$cCrop = Crop::model()
			->select([
				'id',
				'farm', 'sequence', 'plant',
				'startWeek', 'startAction',
				'distance', 'rows', 'rowSpacing', 'plantSpacing', 'density',
				'seedling', 'seedlingSeeds', 'yieldExpected', 'mainUnit'
			])
			->whereSequence($eSequence)
			->getCollection(NULL, NULL, 'id');

		foreach($cCrop as $eCrop) {

			// Mise à jour de l'itinéraire
			$eCrop['id'] = NULL;
			$eCrop['sequence'] = $eSequenceNew;

			// La boucle permet de peupler l'ID
			Crop::model()->insert($eCrop);

		}

		return $cCrop;

	}

	private static function duplicateSlices(Sequence $eSequence, Sequence $eSequenceNew, \Collection $cCrop): \Collection {

		$cSlice = Slice::model()
			->select(Slice::model()->getProperties())
			->whereSequence($eSequence)
			->getCollection(NULL, NULL, 'id');

		// Copie des tâches
		foreach($cSlice as $eSlice) {

			$eSlice['id'] = NULL;
			$eSlice['sequence'] = $eSequenceNew;
			$eSlice['crop'] = $cCrop[$eSlice['crop']['id']];

			Slice::model()->insert($eSlice);

		}

		return $cSlice;

	}

	private static function duplicateRequirements(Sequence $eSequence, Sequence $eSequenceNew, \Collection $cCrop, \Collection $cFlow): \Collection {

		$cRequirement = Requirement::model()
			->select([
				'id',
				'farm', 'crop', 'flow', 'tool'
			])
			->whereSequence($eSequence)
			->getCollection(NULL, NULL, 'id');

		foreach($cRequirement as $eRequirement) {

			// Mise à jour de l'outil
			$eRequirement['id'] = NULL;
			$eRequirement['sequence'] = $eSequenceNew;
			$eRequirement['flow'] = $cFlow[$eRequirement['flow']['id']];

			if($eRequirement['crop']->notEmpty()) {
				$eRequirement['crop'] = $cCrop[$eRequirement['crop']['id']];
			}

			// La boucle permet de peupler l'ID
			Requirement::model()->insert($eRequirement);

		}

		return $cRequirement;

	}

	public static function update(Sequence $e, array $properties): void {

		$e->expects(['id', 'use']);

		Sequence::model()->beginTransaction();

		Sequence::model()
			->select($properties)
			->update($e);

		// Mise à jour de l'utilisation libre ou planche => mettre à jour les densités
		if(in_array('use', $properties)) {

			switch($e['use']) {

				case Sequence::BED :
					Crop::model()
						->whereSequence($e)
						->whereDistance(Crop::SPACING)
						->update([
							'rowSpacing' => NULL,
							'density' => NULL
						]);
					break;

				case Sequence::BLOCK :
					Crop::model()
						->whereSequence($e)
						->whereDistance(Crop::SPACING)
						->update([
							'rows' => NULL,
							'density' => NULL
						]);
					break;

			}

		}

		if(
			array_intersect(['bedWidth', 'alleyWidth'], $properties) and
			$e['use'] === Sequence::BED
		) {
			CropLib::updateDensityBySequence($e);
		}

		Sequence::model()->commit();

	}

	public static function delete(Sequence $e): void {

		$e->expects(['id']);

		Sequence::model()->beginTransaction();

		Sequence::model()
			->whereDuplicateOf($e)
			->update([
				'duplicateOf' => new Sequence()
			]);

		Crop::model()
			->whereSequence($e)
			->delete();

		Flow::model()
			->whereSequence($e)
			->delete();

		Requirement::model()
			->whereSequence($e)
			->delete();

		Slice::model()
			->whereSequence($e)
			->delete();

		\gallery\Photo::model()
			->whereSequence($e)
			->delete();

		\series\Series::model()
			->whereSequence($e)
			->update([
				'sequence' => new Sequence()
			]);

		\series\Cultivation::model()
			->whereSequence($e)
			->update([
				'sequence' => new Sequence(),
				'crop' => new Crop()
			]);

		Sequence::model()->delete($e);

		Sequence::model()->commit();

	}

	public static function recalculate(\farm\Farm $eFarm, Sequence $e): void {

		self::doRecalculate(
			Crop::model(),
			Flow::model(),
			'sequence',
			'crop',
			new \Sql('MIN(IF(weekOnly IS NOT NULL, CAST(weekOnly AS SIGNED) + yearOnly * 100, CAST(weekStart AS SIGNED) + yearStart * 100))', 'int'),
			new \Sql('MIN(IF(weekOnly IS NOT NULL, CAST(weekOnly AS SIGNED) + yearOnly * 100, CAST(weekStop AS SIGNED) + yearStop * 100))', 'int'),
			$eFarm,
			$e,
			['id', 'plant']
		);

	}

	public static function doRecalculate(
		\ModuleModel $mCrop,
		\ModuleModel $mFlow,
		string $propertySequence,
		string $propertyCrop,
		\Sql $start,
		\Sql $stop,
		\farm\Farm $eFarm,
		\Element $e,
		array $selectCrop
	): \Collection {

		$e->expects(['cycle']);

		$cAction = \farm\Action::model()
			->select('id', 'fqn')
			->whereFqn('IN', [ACTION_PLANTATION, ACTION_SEMIS_DIRECT])
			->whereFarm($eFarm)
			->getCollection( index: 'id');

		$cCrop = $mCrop
			->select($selectCrop)
			->where($propertySequence, $e)
			->getCollection(NULL, NULL, 'id');

		foreach($cCrop as $eCrop) {
			
			$eCrop->merge([
				'startWeek' => NULL,
				'startAction' => NULL
			]);

		}


		// Date d'implantation
		$cFlow = $mFlow
			->select([
				'start' => $start,
				'stop' => $stop,
				'action',
				$propertyCrop => [
					'plant' => ['fqn']
				]
			])
			->whereAction('IN', $cAction)
			->where($propertySequence, $e)
			->group([$propertyCrop, 'action'])
			->sort(new \Sql('('.$propertyCrop.' IS NULL)'))
			->getCollection();

		foreach($cFlow as $eFlow) {

			$actionFqn = $cAction[$eFlow['action']['id']]['fqn'];

			// Cas des actions spécifiques traité en premier
			if($eFlow[$propertyCrop]->notEmpty()) {

				$currentCrop = $eFlow[$propertyCrop]['id'];

				switch($actionFqn) {

					case ACTION_PLANTATION :
					case ACTION_SEMIS_DIRECT :
						if($cCrop[$currentCrop]['startWeek'] === NULL or $eFlow['start'] < $cCrop[$currentCrop]['startWeek']) {
							$cCrop[$currentCrop]['startWeek'] = $eFlow['start'];
							$cCrop[$currentCrop]['startAction'] = match($actionFqn) {
								ACTION_PLANTATION => Crop::PLANTING,
								ACTION_SEMIS_DIRECT => Crop::SOWING,
							};
						}
						break;

				}

			}
			// Cas des actions partagées traité après
			else {

				foreach($cCrop as $currentCrop => $currentValue) {

					switch($actionFqn) {

						case ACTION_PLANTATION :
						case ACTION_SEMIS_DIRECT :
							if($currentValue['startWeek']=== NULL or $eFlow['start'] < $currentValue['startWeek']) {
								$cCrop[$currentCrop]['startWeek'] = $eFlow['start'];
								$cCrop[$currentCrop]['startAction'] = match($actionFqn) {
									ACTION_PLANTATION => Crop::PLANTING,
									ACTION_SEMIS_DIRECT => Crop::SOWING,
								};
							}
							break;

					}

				}

			}

		}

		foreach($cCrop as $eCrop) {

			$mCrop
				->select(['startWeek', 'startAction'])
				->update($eCrop);

		}

		return $cCrop;

	}

}
?>
