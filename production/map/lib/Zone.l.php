<?php
namespace map;

class ZoneLib extends ZoneCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'area', 'coordinates', 'seasonFirst'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'area', 'coordinates', 'seasonFirst', 'seasonLast'];
	}

	public static function getByFarm(\farm\Farm $eFarm, mixed $id = NULL, ?int $season = NULL): \Collection|Zone {

		if($id !== NULL) {
			Zone::model()->whereId($id);
		}

		SeasonLib::whereSeason(Zone::model(), $season);

		Zone::model()
			->select(Zone::getSelection())
			->whereFarm($eFarm);

		if($id !== NULL) {
			return Zone::model()->get();
		} else {
			return Zone::model()
				->getCollection()
				->sort('name', natural: TRUE);
		}


	}

	public static function test(\Collection $cZone, \Search $search, \series\Series $eSeries): void {

		$eSeries->expects([
			'use',
			'bedStartCalculated', 'bedStopCalculated',
			'farm' => ['rotationExclude']
		]);

		if($eSeries['use'] !== \series\Series::BED) {
			return;
		}

		$rotations = [];
		
		$cFamily = $eSeries['cCultivation']
			->getColumnCollection('plant')
			->getColumnCollection('family');

		if($cFamily->notEmpty()) {

			$rotationLimit = 5;

			$cCultivationRotation = \series\Cultivation::model()
				->select([
					'series',
					'rotation' => new \Sql($eSeries['season'].' - season', 'int')
				])
				->join(\plant\Plant::model(), 'm1.plant = m2.id')
				->where('m1.series', '!=', $eSeries)
				->where('m1.farm', $eSeries['farm'])
				->whereSeason('BETWEEN', new \Sql(($eSeries['season'] - $rotationLimit).' AND '.$eSeries['season']))
				->where('m2.family', 'IN', $cFamily)
				->where('m1.plant', 'NOT IN', $eSeries['farm']['rotationExclude'], if: $eSeries['farm']['rotationExclude'])
				->getCollection(index: 'series');

			foreach(\series\Place::model()
				->select(['bed', 'series'])
				->whereSeries('IN', $cCultivationRotation->getColumn('series'))
				->getCollection() as $ePlace) {

				$rotations[$ePlace['bed']['id']] = $cCultivationRotation[$ePlace['series']['id']]['rotation'];

			}

		}

		$filterMode = $search->get('mode');

		foreach($cZone as ['cPlot' => $cPlot]) {

			foreach($cPlot as ['cBed' => $cBed]) {

				foreach($cBed as $eBed) {

					if($eBed['plotFill'] or $eBed['zoneFill']) {
						continue;
					}

					$eBed['test'] = [
						'sameWidth' => ($eSeries['bedWidth'] === $eBed['width']),
						'hasGreenhouse' => $eBed['greenhouse']->notEmpty(),
						'rotation' => $rotations[$eBed['id']] ?? NULL,
						'free' => 0,
						'hide' => (
							($filterMode === Plot::GREENHOUSE and $eBed['greenhouse']->empty()) or
							($filterMode === Plot::OPEN_FIELD and $eBed['greenhouse']->notEmpty())
						)
					];

					if(
						$eSeries['bedStartCalculated'] !== NULL and
						$eSeries['bedStopCalculated'] !== NULL
					) {

						foreach($eBed['cPlace'] as $ePlace) {

							if(
								$ePlace['series']->empty() or
								$ePlace['series']['id'] === $eSeries['id']
							) {
								continue;
							}

							if(
								$ePlace['series']['bedStartCalculated'] === NULL or
								$ePlace['series']['bedStopCalculated'] === NULL
							) {

								if($ePlace['series']['cycle'] === \series\Series::PERENNIAL) {

									$eBed['test']['free'] = NULL;
									break;

								} else {
									continue;
								}

							}

							$placeStart = $ePlace['series']['bedStartCalculated'] + ($ePlace['series']['season'] - $eSeries['season']) * 100;
							$placeStop = $ePlace['series']['bedStopCalculated'] + ($ePlace['series']['season'] - $eSeries['season']) * 100;

							$free = min($placeStop - $eSeries['bedStartCalculated'], $eSeries['bedStopCalculated'] - $placeStart);

							if($free <= 2) {
								$eBed['test']['free'] = max($eBed['test']['free'], max($free, 0));
							} else {
								$eBed['test']['free'] = NULL;
								break;
							}

						}

					}


				}

			}

		}

	}

	public static function create(Zone $e): void {

		Zone::model()->beginTransaction();

		Zone::model()->insert($e);

		// On crée le jardin inféodé à la parcelle
		PlotLib::createFromZone($e);

		Zone::model()->commit();

	}

	public static function update(Zone $e, array $properties): void {

		$e['updatedAt'] = new \Sql('NOW()');
		$properties[] = 'updatedAt';

		Zone::model()->beginTransaction();

		Zone::model()
			->select($properties)
			->update($e);

		if(in_array('area', $properties)) {

			Plot::model()
				->whereZoneFill(TRUE)
				->whereZone($e)
				->update([
					'area' => $e['area']
				]);

			Bed::model()
				->whereZoneFill(TRUE)
				->whereZone($e)
				->update([
					'area' => $e['area']
				]);

		}

		if(in_array('seasonFirst', $properties)) {

			Plot::model()
				->whereZone($e)
				->whereZoneFill(TRUE)
				->update([
					'seasonFirst' => $e['seasonFirst']
				]);

			Bed::model()
				->whereZone($e)
				->whereZoneFill(TRUE)
				->update([
					'seasonFirst' => $e['seasonFirst']
				]);

			if($e['seasonFirst'] !== NULL) {

				Plot::model()
					->whereZone($e)
					->where('seasonFirst <'.$e['seasonFirst'])
					->update([
						'seasonFirst' => $e['seasonFirst']
					]);

				Bed::model()
					->whereZone($e)
					->where('seasonFirst <'.$e['seasonFirst'])
					->whereStatus(Bed::ACTIVE)
					->update([
						'seasonFirst' => $e['seasonFirst']
					]);

			}

		}

		if(in_array('seasonLast', $properties)) {

			Plot::model()
				->whereZone($e)
				->whereZoneFill(TRUE)
				->update([
					'seasonLast' => $e['seasonLast']
				]);

			Bed::model()
				->whereZone($e)
				->whereZoneFill(TRUE)
				->update([
					'seasonLast' => $e['seasonLast']
				]);

			if($e['seasonLast'] !== NULL) {

				Plot::model()
					->whereZone($e)
					->where('seasonLast >'.$e['seasonLast'])
					->update([
						'seasonLast' => $e['seasonLast']
					]);

				Bed::model()
					->whereZone($e)
					->where('seasonLast >'.$e['seasonLast'])
					->whereStatus(Bed::ACTIVE)
					->update([
						'seasonLast' => $e['seasonLast']
					]);

			}

		}

		Zone::model()->commit();

	}

	public static function delete(Zone $e): void {

		$e->expects(['id']);

		if(Greenhouse::model()
			->whereZone($e)
			->exists()) {
			Zone::fail('greenhouse');
			return;
		}

		if(\series\Place::model()
				->whereZone($e)
				->exists()) {
			Zone::fail('deleteUsed');
			return;
		}

		Zone::model()->beginTransaction();

		Plot::model()
			->whereZone($e)
			->delete();

		Bed::model()
			->whereZone($e)
			->delete();

		Zone::model()->delete($e);

		Zone::model()->commit();

	}

}
?>
