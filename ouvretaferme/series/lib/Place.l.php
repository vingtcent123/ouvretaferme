<?php
namespace series;

class PlaceLib extends PlaceCrud {

	public static function getByElement(Series|Task $e): \Collection {

		$cPlace = Place::model()
			->select([
				'id',
				'zone' => ['name'],
				'plot' => ['name'],
				'bed' => [
					'name', 'length', 'area', 'width',
					'plotFill',
					'greenhouse' => ['name'],
				],
				'length', 'width', 'area'
			])
			->whereSeries($e, if: $e instanceof Series)
			->whereTask($e, if: $e instanceof Task)
			->getCollection(NULL, NULL, 'bed');

		$cPlace->sort([
			'zone' => ['name'],
			'plot' => ['name'],
			'bed' => ['name']
		], natural: TRUE);

		return $cPlace;

	}

	public static function delegateByTask(): PlaceModel {

		return (new PlaceModel())
			->select([
				'task',
				'zone' => ['name'],
				'plot' => ['name', 'zoneFill'],
				'bed' => ['name', 'plotFill']
			])
			->sort(['plot' => SORT_ASC, 'bed' => SORT_ASC])
			->delegateCollection('task', ['zone', 'plot', NULL]);

	}

	public static function delegateBySeries(): PlaceModel {

		return (new PlaceModel())
			->select([
				'series',
				'zone' => ['name'],
				'plot' => ['name', 'zoneFill'],
				'bed' => ['name', 'plotFill']
			])
			->sort(['plot' => SORT_ASC, 'bed' => SORT_ASC])
			->delegateCollection('series', ['zone', 'plot', NULL]);

	}

	public static function buildFromBeds(string $source, Series|Task $e, array $beds, array $sizes): \Collection {

		$e->expects(['farm', 'season', 'use']);

		$cBed = \map\Bed::model()
			->select([
				'id',
				'plot', 'plotFill', 'zone', 'zoneFill', 'greenhouse',
				'length', 'width', 'area'
			])
			->whereId('IN', $beds)
			->whereFarm($e['farm'])
			->getCollection();

		if($cBed->count() !== count($beds)) {
			Place::fail('bedsCheck');
			return new \Collection();
		}

		$cPlace = new \Collection();

		foreach($cBed as $eBed) {

			$ePlace = new Place([
				'farm' => $e['farm'],
				'season' => $e['season'],
				$source => $e,
				'bed' => $eBed,
				'plot' => $eBed['plot'],
				'zone' => $eBed['zone'],
				'greenhouse' => $eBed['greenhouse'],
				'length' => NULL,
				'width' => NULL,
				'area' => NULL
			]);

			switch($source) {

				case 'series' :

					$size = (int)($sizes[$eBed['id']] ?? 0);

					switch($e['use']) {

						case Series::BED :

							if(
								$size <= 0 or
								($eBed['length'] !== NULL and $size > $eBed['length'])
							) {
								Place::fail('bedsSize');
								return new \Collection();
							}

							$ePlace['length'] = $size;
							$ePlace['width'] = $eBed['width'] /* Planche permanente */ ?? $e['bedWidth'] /* Planche temporaire */;

							$ePlace['area'] = $size * ($ePlace['width'] + $e['alleyWidth'] ?? 0) / 100;

							break;

						case Series::BLOCK :

							if($size <= 0 or $size > $eBed['area']) {
								Place::fail('bedsSize');
								return new \Collection();
							}

							$ePlace['area'] = $size;

							break;

					}

					break;

				case 'task' :

					$ePlace['length'] = NULL;
					$ePlace['width'] = NULL;
					$ePlace['area'] = NULL;

					break;

			}

			$cPlace[] = $ePlace;

		}

		return $cPlace;

	}

	public static function recalculateAreaBySeries(Series $eSeries): \Collection {

		$eSeries->expects(['id', 'alleyWidth']);

		$cPlace = self::getByElement($eSeries);

		$fullArea = NULL;
		$fullAreaPermanent = NULL;

		foreach($cPlace as $ePlace) {


			switch($eSeries['use']) {

				case Series::BED :

					$ePlace['area'] = $ePlace['length'] * ($ePlace['width'] + $eSeries['alleyWidth'] ?? 0) / 100;

					Place::model()
						->select('area')
						->update($ePlace);

					if($ePlace['bed']['width'] !== NULL) {
						$fullAreaPermanent ??= 0;
						$fullAreaPermanent += $ePlace['area'];
					}

					break;

			}

			$fullArea ??= 0;
			$fullArea += $ePlace['area'];

		}

		$eSeries['area'] = $fullArea;
		$eSeries['areaPermanent'] = $fullAreaPermanent;

		Series::model()
			->select(['area', 'areaPermanent'])
			->update($eSeries);

		Cultivation::model()
			->whereSeries($eSeries)
			->update([
				'area' => $eSeries['area'],
				'areaPermanent' => $eSeries['areaPermanent'],
			]);

		return $cPlace;

	}

	public static function replaceForSeries(Series $eSeries, \Collection $cPlace): void {

		Series::model()->beginTransaction();

		self::recalculateMetadata($eSeries, $cPlace);

		Place::model()
			->whereSeries($eSeries)
			->delete();

		self::updateMetadata($eSeries);

		try {
			Place::model()->insert($cPlace);
		} catch(\DuplicateException) {

			Place::fail('bedsDuplicate');

			Series::model()->rollBack();
			return;

		}

		Series::model()->commit();

	}

	public static function replaceForTask(Task $eTask, \Collection $cPlace): void {

		Series::model()->beginTransaction();

		Place::model()
			->whereTask($eTask)
			->delete();

		try {
			Place::model()->insert($cPlace);
		} catch(\DuplicateException) {

			Place::fail('bedsDuplicate');

			Series::model()->rollBack();
			return;

		}

		Series::model()->commit();

	}

	public static function recalculateMetadata(Series $eSeries, \Collection $cPlace): void {

		$fullArea = NULL;
		$fullAreaPermanent = NULL;

		$fullLength = NULL;
		$fullLengthPermanent = NULL;

		foreach($cPlace as $ePlace) {

			$eBed = $ePlace['bed'];

			switch($eSeries['use']) {

				case Series::BED :

					$fullLength ??= 0;
					$fullLength += $ePlace['length'];

					$fullArea ??= 0;
					$fullArea += $ePlace['area'];

					if($eBed['width'] !== NULL) {

						$fullLengthPermanent ??= 0;
						$fullLengthPermanent += $ePlace['length'];

						$fullAreaPermanent ??= 0;
						$fullAreaPermanent += $ePlace['area'];

					}

					break;

				case Series::BLOCK :

					$fullArea ??= 0;
					$fullArea += $ePlace['area'];

					break;

			}

		}

		$eSeries['area'] = $fullArea;
		$eSeries['areaPermanent'] = $fullAreaPermanent;

		$eSeries['length'] = $fullLength;
		$eSeries['lengthPermanent'] = $fullLengthPermanent;

	}

	public static function updateMetadata(Series $eSeries): void {

		Series::model()
			->select(['area', 'areaPermanent', 'length', 'lengthPermanent'])
			->update($eSeries);

		Cultivation::model()
			->whereSeries($eSeries)
			->update([
				'area' => $eSeries['area'],
				'areaPermanent' => $eSeries['areaPermanent'],
				'length' => $eSeries['length'],
				'lengthPermanent' => $eSeries['lengthPermanent']
			]);

	}

	public static function getForRotations(\farm\Farm $eFarm, \Collection $cBed, array $seasons) {

		$eFarm->expects(['rotationExclude']);

		return Place::model()
			->select([
				'season',
				'bed' => ['length', 'area']
			])
			->join(Cultivation::model()->select([
				'plant' => ['name', 'vignette', 'family']
			]), 'm1.series = m2.series', type: 'LEFT')
			->join(\plant\Plant::model()->select([
				'family' => ['name', 'fqn']
			]), 'm2.plant = m3.id', type: 'LEFT')
			->where('m1.season', 'IN', $seasons)
			->where('m1.bed', 'IN', $cBed)
			->where('m2.plant', 'NOT IN', $eFarm['rotationExclude'], if: $eFarm['rotationExclude'])
			->group(['m1.season', 'family', 'bed', 'plant'])
			->getCollection(index: ['season', 'family', 'bed', NULL]);


	}

	public static function getRotationsStats(\Collection $ccccPlace): array {

		$stats = [];

		foreach($ccccPlace as $season => $cccPlace) {

			foreach($cccPlace as $family => $ccPlace) {

				foreach($ccPlace as $bed => $cPlace) {

					$stats[$family]['season'][$season] ??= [];
					$stats[$family]['season'][$season][$bed] = [
						'bed' => $cPlace->first()['bed'],
						'plants' => $cPlace->getColumnCollection('plant')
					];

					$stats[$family]['bed'][$bed] ??= 0;
					$stats[$family]['bed'][$bed]++;

				}

			}

		}

		return $stats;


	}

	public static function filterRotationsByFamily(\farm\Farm $eFarm, \Collection $cZone, \Search $search): void {

		$eFamily = $search->get('family');

		if(
			$eFamily->empty(['cFamily']) and
			$search->get('bed') === FALSE
		) {
			return;
		}

		$seenSearch = $search->get('seen') ?? 1;
		$seasonSearch = NULL;

		if($seenSearch > 1000) {
			$seasonSearch = $seenSearch;
			$seenSearch = NULL;
		}

		$onlyBed = $search->get('bed');

		foreach($cZone as $eZone) {

			foreach($eZone['cPlot'] as $ePlot) {

				$delete = [];

				foreach($ePlot['cBed'] as $bed => $eBed) {

					if(
						$onlyBed and
						($eBed['plotFill'] or $eBed['zoneFill'])
					) {
						$delete[] = $bed;
						continue;
					}

					if($eFamily->notEmpty()) {

						$seasons = [];

						foreach($eBed['cPlace'] as $ePlace) {

							$cCultivation = $ePlace['series']['cCultivation'];

							foreach($cCultivation as $eCultivation) {

								if(
									in_array($eCultivation['plant']['id'], $eFarm['rotationExclude']) === FALSE and
									$eCultivation['plant']['family']->notEmpty() and
									$eCultivation['plant']['family']['id'] === $eFamily['id']
								) {
									$seasons[] = $eCultivation['season'];
								}

							}

						}

						$seasons = array_unique($seasons);
						sort($seasons);

						// Famille pas trouvée
						if($seasons === []) {

							// Famille recherchée, on la supprime
							if($seenSearch > 0 or $seasonSearch > 0) {
								$delete[] = $bed;
							}

						}
						// Famille trouvée
						else {

							// Famille pas recherche, on la supprime
							if($seenSearch === 0 and $seasonSearch === NULL) {
								$delete[] = $bed;
							}
							// Famille cherchée N fois et pas trouvée N fois
							else if($seenSearch !== NULL and count($seasons) !== $seenSearch) {
								$delete[] = $bed;
							}
							// Famille cherchée en saison N et pas trouvée
							else if($seasonSearch !== NULL and last($seasons) !== $seasonSearch) {
								$delete[] = $bed;
							}

						}

					}

				}


				foreach($delete as $bed) {
					$ePlot['cBed']->offsetUnset($bed);
				}

			}

		}


	}

	public static function deleteBySeries(Series $e): void {

		Place::model()
			->whereSeries($e)
			->delete();

	}

}
?>