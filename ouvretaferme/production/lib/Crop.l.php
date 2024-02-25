<?php
namespace production;

class CropLib extends CropCrud {

	public static function getPropertiesCreate(): array {
		return ['plant', 'distance', 'density', 'rows', 'plantSpacing', 'rowSpacing', 'seedling', 'seedlingSeeds', 'yieldExpected', 'mainUnit', 'variety'];
	}

	public static function getPropertiesUpdate(): array {
		return ['yieldExpected', 'mainUnit', 'plant', 'distance', 'density', 'rows', 'plantSpacing', 'rowSpacing', 'seedling', 'seedlingSeeds', 'variety'];
	}

	public static function getBySequence(Sequence $eSequence): \Collection {

		return Crop::model()
			->select(Crop::getSelection())
			->whereSequence($eSequence)
			->getCollection(NULL, NULL, 'id');

	}

	public static function getFromQuery(\farm\Farm $eFarm, \Collection $cAction, string $query, \Search $search = new \Search()): \Collection {

		if(str_starts_with($query, '#') and ctype_digit(substr($query, 1))) {
			Crop::model()->where('m2.id', substr($query, 1));
		} else if($query !== '') {
			Crop::model()->where('m2.name', 'LIKE', '%'.$query.'%');
		}

		$cPlant = \plant\PlantLib::getFromQuery($query, $eFarm);

		Crop::model()->select([
			'cCrop' => (new CropModel())
				->select(Crop::getSelection())
				->delegateCollection('sequence', 'id', fn($c) => $c->sort(CropLib::sortByPlant()), propertyParent: 'sequence')
		]);

		return self::getByFarm($eFarm, $cAction, TRUE, $search, cPlantPriority: $cPlant);

	}

	public static function getByFarm(\farm\Farm $eFarm, \Collection $cAction, bool $indexByPlant, \Search $search = new \Search(), \Collection $cPlantPriority = new \Collection()): \Collection {

		if($search->has('tool') and $search->get('tool')->notEmpty()) {

			$cSequenceRequirement = Requirement::model()
				->whereFarm($eFarm)
				->whereTool($search->get('tool'))
				->getColumn('sequence');

		} else {
			$cSequenceRequirement = new \Collection();
		}

		$cCrop = Crop::model()
			->select([
				'sequence' => SequenceElement::getSelection(),
				'cFlow' => Flow::model()
					->select(Flow::getSelection())
					->whereAction('IN', $cAction)
					->sort(new \Sql('IF(seasonStart IS NOT NULL, seasonStart, seasonOnly) ASC, IF(yearStart IS NOT NULL, yearStart, yearOnly) ASC, IF(weekStart IS NOT NULL, weekStart, weekOnly) ASC'))
					->delegateCollection('crop', ['action', NULL])
			] + Crop::getSelection())
			->join(Sequence::model(), 'm2.id = m1.sequence')
			->where('m1.farm', $eFarm)
			->where('m1.sequence', 'IN', $search->get('sequences'), if: $search->get('sequences'))
			->where('m1.plant', $search->get('plant'), if: $search->get('plant')?->notEmpty())
			->where('m1.plant', 'IN', $search->get('plants'), if: $search->get('plants')?->notEmpty())
			->where('m2.id', 'IN', $cSequenceRequirement, if: $cSequenceRequirement->notEmpty())
			->where('m2.name', 'LIKE', '%'.$search->get('name').'%', if: $search->get('name'))
			->where('m2.use', $search->get('use'), if: $search->get('use'))
			->where('m2.status', $search->get('status', Sequence::ACTIVE))
			->sort([
				'startWeek' => SORT_ASC,
				'name' => SORT_ASC
			])
			->getCollection(index: $indexByPlant ? ['plant', NULL] : NULL);

		if($indexByPlant) {
			\series\CultivationLib::orderByPlant($cCrop, $cPlantPriority);
		}

		return $cCrop;

	}

	public static function sortByPlant(): \Closure {
		return function(Crop $e1, Crop $e2) {
			return \L::getCollator()->compare($e1['plant']['name'], $e2['plant']['name']);
		};
	}

	public static function create(Crop $e): void {

		$e->expects([
			'sequence' => ['farm'],
			'cSlice'
		]);

		$e['farm'] = $e['sequence']['farm'];

		SliceLib::createVariety($e['cSlice']);

		self::calculateDistance($e, $e['sequence']);

		Crop::model()->beginTransaction();

		try {

			parent::create($e);

			$fw = new \FailWatch();

			// Ajout de la répartition des variétés
			SliceLib::createCollection($e['cSlice']);

			if($fw->ok()) {

				Sequence::model()->update($e['sequence'], [
					'plants' => new \Sql('plants + 1')
				]);

				Crop::model()->commit();

			} else {
				Crop::model()->rollBack();
			}

		}
		catch(\DuplicateException) {

			Crop::fail('plant.duplicate');

			Crop::model()->rollBack();

		}

	}

	public static function update(Crop $e, array $properties): void {

		Crop::model()->beginTransaction();

		$key = array_search('variety', $properties);

		if($key !== FALSE) {

			SliceLib::createVariety($e['cSlice']);

			SliceLib::deleteByCrop($e);
			SliceLib::createCollection($e['cSlice']);

			unset($properties[$key]);

		}

		$distanceUpdate = array_intersect(['distance', 'density', 'rows', 'rowSpacing', 'plantSpacing'], $properties);

		if(count($distanceUpdate) === 5) {
			self::calculateDistance($e, $e['sequence']);
		} else if(count($distanceUpdate) > 0) {
			throw new \Exception('Properties must be updated together');
		}

		parent::update($e, $properties);

		// Mise à jour l'espèce -> mettre à jour également le flow
		if(in_array('plant', $properties)) {

			Flow::model()
				->whereCrop($e)
				->update([
					'plant' => $e['plant']
				]);

		}

		Crop::model()->commit();

	}

	public static function updateDensityBySequence(Sequence $eSequence): void {

		$eSequence->expects(['bedWidth']);

		Crop::model()
			->whereSequence($eSequence)
			->whereDistance(Crop::SPACING)
			->update([
				'density' => new \Sql('IF('.Crop::model()->field('rows').' IS NOT NULL AND plantSpacing IS NOT NULL, '.Crop::model()->field('rows').' / ('.($eSequence['bedWidth'] + $eSequence['alleyWidth'] ?? 0).' / 100) * 100 / plantSpacing, NULL)')
			]);

	}

	public static function getHarvestsFromFlow(\Collection $cFlow, int $referenceYear, string $group = 'week'): array {

		$harvests = [];

		foreach($cFlow as $eFlow) {

			if($eFlow['action']['fqn'] !== ACTION_RECOLTE) {
				continue;
			}

			$ePlant = $eFlow['plant'];

			if($eFlow['weekOnly'] !== NULL) {

				$week = ($referenceYear + $eFlow['yearOnly']).'-W'.sprintf('%02d', $eFlow['weekOnly']);

				$harvests[$ePlant['id']][] = match($group) {
					'week' => $week,
					'month' => \util\DateLib::convertWeeksToMonths([$week])[0]
				};

			} else {

				for($year = $eFlow['yearStart']; $year <= $eFlow['yearStop']; $year++) {

					for(
						$weekNumber = ($year === $eFlow['yearStart'] ? $eFlow['weekStart'] : 1);
						$weekNumber <= ($year === $eFlow['yearStop'] ? $eFlow['weekStop'] : 52);
						$weekNumber++
					) {

						$week = ($referenceYear + $year).'-W'.sprintf('%02d', round($weekNumber));

						$harvests[$ePlant['id']][] = match($group) {
							'week' => $week,
							'month' => \util\DateLib::convertWeeksToMonths([$week])[0]
						};

					}

				}

			}

		}

		foreach($harvests as $plant => $list) {

			$harvests[$plant] = array_unique($list);
			natsort($harvests[$plant]);

		}

		return $harvests;

	}

	public static function calculateDistance(Crop|\series\Cultivation $eCrop, Sequence|\series\Series $eSequence): void {

		$eCrop->expects([
			'distance', 'rows', 'rowSpacing', 'plantSpacing', 'density'
		]);

		$eSequence->expects(['use', 'bedWidth', 'alleyWidth']);

		switch($eCrop['distance']) {

			case Crop::SPACING :
				$eCrop['density'] = self::calculateDensity($eCrop, $eSequence);
				break;

			case Crop::DENSITY :
				$eCrop['rows'] = NULL;
				$eCrop['rowSpacing'] = NULL;
				$eCrop['plantSpacing'] = NULL;
				break;

		}

	}

	public static function calculateDensity(Crop|\series\Cultivation $eCrop, Sequence|\series\Series $eSequence): ?float {

		return match($eSequence['use']) {
			Sequence::BLOCK => ($eCrop['rowSpacing'] !== NULL and $eCrop['plantSpacing'] !== NULL) ? (100 / $eCrop['rowSpacing'] * 100 / $eCrop['plantSpacing']) : NULL,
			Sequence::BED => ($eCrop['rows'] !== NULL and $eCrop['plantSpacing'] !== NULL) ? ($eCrop['rows'] / ($eSequence['bedWidth'] / 100) * 100 / $eCrop['plantSpacing']) : NULL
		};

	}

	public static function delete(Crop $e): void {

		$e->expects(['id', 'sequence']);

		if(
			Crop::model() // Il doit rester au moins une culture
				->whereSequence($e['sequence'])
				->count() === 1) {
			Crop::fail('deleteOnly');
			return;
		}

		Crop::model()->beginTransaction();

		Flow::model()
			->whereCrop($e)
			->delete();

		Requirement::model()
			->whereSequence($e['sequence']) // Pour l'index
			->whereCrop($e)
			->delete();

		\series\Cultivation::model()
				->whereCrop($e)
				->update([
					'crop' => NULL
				]);

		Crop::model()->delete($e);

		// Mets à jour le compteur de productions
		$cCrop = self::getBySequence($e['sequence']);

		$e['sequence']['plants'] = $cCrop->count();

		Sequence::model()
			->select('plants')
			->update($e['sequence']);

		// on passe les actions générales sur la culture restante
		if($e['sequence']['plants'] === 1) {

			$eCropRemaining = $cCrop->first();

			Flow::model()
				->whereSequence($e['sequence'])
				->whereCrop(NULL)
				->update([
					'crop' => $eCropRemaining
				]);

			Requirement::model()
				->whereSequence($e['sequence'])
				->whereCrop(NULL)
				->update([
					'crop' => $eCropRemaining
				]);

		}

		Crop::model()->commit();

	}

}
?>
