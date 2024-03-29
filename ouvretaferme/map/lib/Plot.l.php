<?php
namespace map;

class PlotLib extends PlotCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'mode', 'greenhouse', 'area', 'coordinates', 'seasonFirst'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'area', 'coordinates', 'seasonFirst', 'seasonLast'];
	}

	public static function getByFarm(\farm\Farm $eFarm, mixed $id = NULL, ?int $season = NULL): \Collection|Plot {

		if($id !== NULL) {
			Plot::model()->whereId($id);
		}

		SeasonLib::whereSeason(Plot::model(), $season);

		Plot::model()
			->select(Plot::getSelection())
			->whereFarm($eFarm);

		if($id !== NULL) {
			return Plot::model()->get();
		} else {
			return Plot::model()
				->getCollection()
				->sort('name', natural: TRUE);
		}


	}

	public static function getByZone(Zone $eZone): \Collection {

		return Plot::model()
			->select(Plot::getSelection())
			->whereZone($eZone)
			->sort(['name' => SORT_ASC])
			->getCollection();

	}

	public static function createFromZone(Zone $eZone): void {

		$e = new Plot([
			'zone' => $eZone,
			'zoneFill' => TRUE,
			'mode' => Plot::OUTDOOR,
			'area' => $eZone['area']
		]);

		self::create($e);

	}

	public static function create(Plot $e): void {

		$e->expects([
			'zone' => ['farm'],
			'area'
		]);

		$e->add([
			'zoneFill' => FALSE
		]);

		Plot::model()->beginTransaction();

		$e['farm'] = $e['zone']['farm'];

		Plot::model()->insert($e);

		// On crée une planche inféodée au bloc
		$eBed = new Bed([
			'zone' => $e['zone'],
			'zoneFill' => $e['zoneFill'],
			'plot' => $e,
			'plotFill' => TRUE,
			'area' => $e['area']
		]);

		BedLib::create($eBed);

		if($e['mode'] === Plot::GREENHOUSE) {

			GreenhouseLib::createForPlot($e);

		}

		Plot::model()->commit();

	}

	public static function update(Plot $e, array $properties): void {

		$e['updatedAt'] = new \Sql('NOW()');
		$properties[] = 'updatedAt';

		Plot::model()->beginTransaction();

		Plot::model()
			->select($properties)
			->update($e);

		if(in_array('area', $properties)) {

			Bed::model()
				->wherePlot($e)
				->wherePlotFill(TRUE)
				->update([
					'area' => $e['area']
				]);

		}

		if(in_array('seasonFirst', $properties)) {

			Bed::model()
				->wherePlot($e)
				->wherePlotFill(TRUE)
				->update([
					'seasonFirst' => $e['seasonFirst']
				]);

			if($e['seasonFirst'] !== NULL) {

				Bed::model()
					->wherePlot($e)
					->where('seasonFirst <'.$e['seasonFirst'])
					->update([
						'seasonFirst' => $e['seasonFirst']
					]);

			}

		}

		if(in_array('seasonLast', $properties)) {

			Bed::model()
				->wherePlot($e)
				->wherePlotFill(TRUE)
				->update([
					'seasonLast' => $e['seasonLast']
				]);

			if($e['seasonLast'] !== NULL) {

				Bed::model()
					->wherePlot($e)
					->where('seasonLast >'.$e['seasonLast'])
					->update([
						'seasonLast' => $e['seasonLast']
					]);

			}

		}

		Plot::model()->commit();

	}

	public static function putFromZone(\Collection|Zone $value, bool $withBeds = FALSE, bool $withDraw = FALSE, ?int $season = NULL, ?\Closure $newSelection = NULL): void {

		$selection = [
			'id',
			'zone', 'zoneFill', 'name', 'mode',
			'area', 'coordinates',
			'seasonFirst', 'seasonLast',
			'cGreenhouse' => Greenhouse::model()
				->select(Greenhouse::getSelection())
				->delegateCollection('plot'),
		];

		if($withBeds) {

			SeasonLib::whereSeason(Bed::model(), $season);

			$selection['cBed'] = Bed::model()
				->select([
					'id', 'name', 'farm',
					'greenhouse' => ['name'],
					'seasonFirst', 'seasonLast',
					'zoneFill', 'plotFill', 'length', 'width', 'area'
				])
				//->whereId('IN', [ 98])
				->sort(new \Sql('plotFill DESC, name ASC'))
				->delegateCollection('plot', index: 'id', callback: fn(\Collection $cBed) => $cBed->sort('name', natural: TRUE));

		}

		if($withDraw) {

			$selection['cDraw'] = Draw::model()
				->select(Draw::getSelection())
				->whereSeason('<=', $season, if: $season !== NULL)
				->sort(['season' => SORT_DESC])
				->delegateCollection('plot');

		}

		if(is_closure($newSelection)) {
			$newSelection($selection);
		}

		SeasonLib::whereSeason(Plot::model(), $season);

		if(Zone::model()
			->select([
				'cPlot' => Plot::model()
					->select($selection)
					->delegateCollection('zone')
			])
			->get($value)) {

			if($value instanceof Zone) {
				$value['cPlot']->sort('name', natural: TRUE);
			} else {
				foreach($value as $eZone) {
					$eZone['cPlot']->sort('name', natural: TRUE);
				}
			}

		}

		if($withBeds) {
			self::putFromZoneWithBedsStats($value);
		}

		if($withDraw) {
			self::putFromZoneWithDrawStats($value);
		}

	}

	private static function putFromZoneWithBedsStats(\Collection|Zone $value) {

		$calc = function(Zone $e) {

			$plots = 0;
			$zoneBeds = 0;
			$zoneBedsArea = 0;

			foreach($e['cPlot'] as $ePlot) {

				if($ePlot['zoneFill'] === FALSE) {
					$plots++;
				}

				$plotBeds = 0;
				$plotBedsArea = 0;

				foreach($ePlot['cBed'] as $eBed) {

					if($eBed['plotFill']) {
						continue;
					}

					$plotBeds++;
					$plotBedsArea += $eBed['area'];

				}

				$ePlot['beds'] = $plotBeds;
				$ePlot['bedsArea'] = $plotBedsArea;

				$zoneBeds += $plotBeds;
				$zoneBedsArea += $plotBedsArea;

			}

			$e['plots'] = $plots;
			$e['beds'] = $zoneBeds;
			$e['bedsArea'] = $zoneBedsArea;

		};

		if($value instanceof \Collection) {
			$value->map($calc);
		} else {
			$calc($value);
		}

	}

	private static function putFromZoneWithDrawStats(\Collection|Zone $value) {

		$calc = function(Zone $e) {

			foreach($e['cPlot'] as $ePlot) {

				if($ePlot['cDraw']->empty()) {
					$ePlot['cBed']->setColumn('drawn', FALSE);
				} else {

					// On filtre les affichages sur la dernière saison définie
					$seasonSelected = $ePlot['cDraw']->first()['season'];
					$ePlot['cDraw']->filter(fn($eDraw) => $eDraw['season'] === $seasonSelected);

					$bedsDraw = array_flip(array_merge(...$ePlot['cDraw']->getColumn('beds')));

					foreach($ePlot['cBed'] as $eBed) {
						$eBed['drawn'] = array_key_exists($eBed['id'], $bedsDraw);
					}

				}

			}

		};

		if($value instanceof \Collection) {
			$value->map($calc);
		} else {
			$calc($value);
		}

	}

	public static function putFromZoneWithSeries(\farm\Farm $eFarm, \Collection|Zone $value, ?int $season = NULL, array $withSeries = [], bool $onlySeries = FALSE): void {

		self::putFromZone($value, withBeds: TRUE, season: $season, newSelection: function(&$selection) use ($eFarm, $withSeries, $onlySeries) {

			$cAction = \farm\ActionLib::getByFarm($eFarm, fqn: [ACTION_SEMIS_DIRECT, ACTION_PLANTATION]);

			$selection['cBed']->select([
				'cPlace' => \series\Place::model()
					->select([
						'id',
						'area', 'length', 'season',
						'series' => [
							'name', 'season', 'use', 'mode', 'cycle', 'perennialSeason', 'status',
							'bedStartCalculated', 'bedStopCalculated',
							'cCultivation' => \series\Cultivation::model()
								->select([
									'id',
									'season',
									'harvested',
									'harvestMonths', 'harvestMonthsExpected',
									'harvestWeeks', 'harvestWeeksExpected',
									'startWeek', 'startAction',
									'plant' => [
										'name', 'vignette', 'fqn',
										'family' => ['fqn', 'name', 'color']
									],
									'cTask' => \series\Task::model()
										->select([
											'cultivation',
											'action' => \farm\Action::getSelection(),
											'plannedWeek', 'doneWeek',
											'status'
										])
										->whereAction('IN', $cAction)
										->sort(new \Sql('IF(status="'.\series\Task::TODO.'", plannedWeek, doneWeek)'))
										->delegateCollection('cultivation'),
									'firstTaskWeek' => function($e) {

										if($e['cTask']->empty()) {
											return NULL;
										} else {
											$eTask = $e['cTask']->first();
											if($eTask['status'] === \series\Task::TODO) {
												return $eTask['plannedWeek'];
											} else {
												return $eTask['doneWeek'];
											}
										}

									}
								])
								->sort(['startWeek' => SORT_ASC])
								->delegateCollection('series')

						]
					])
					->whereSeries('!=', NULL, if: $onlySeries)
					->whereSeason('IN', $withSeries)
					->delegateCollection('bed', callback: function($cPlace) {

						// Tri des séries par saison puis date de première tâche
						$cPlace->setColumn('firstTaskAt', function($ePlace) {

							if(
								$ePlace['series']->empty() or
								$ePlace['series']['cCultivation']->empty()
							) {
								return 0;
							} else {
								$week = $ePlace['series']['cCultivation']->first()['firstTaskWeek'];
								return $week ? strtotime($week) : NULL;
							}

						});

						$cPlace->sort(function($e1, $e2) {

							if($e1['season'] !== $e2['season']) {
								return ($e1['season'] > $e2['season']) ? 1 : -1;
							}

							return ($e1['firstTaskAt'] > $e2['firstTaskAt'] ? 1 : -1);

						});

						return $cPlace;

					})
			]);

		});

		self::putFromZoneWithSeriesStats($value);

	}

	private static function putFromZoneWithSeriesStats(\Collection|Zone $value) {

		$calc = function(Zone $e) {

			$zonePlaces = 0;

			foreach($e['cPlot'] as $ePlot) {

				$plotPlaces = 0;

				foreach($ePlot['cBed'] as $eBed) {
					$plotPlaces += $eBed['cPlace']->count();
				}

				$ePlot['places'] = $plotPlaces;

				$zonePlaces += $plotPlaces;

			}

			$e['places'] = $zonePlaces;

		};

		if($value instanceof \Collection) {
			$value->map($calc);
		} else {
			$calc($value);
		}

	}

	public static function delete(Plot $e): void {

		$e->expects(['id']);

		if(Greenhouse::model()
				->wherePlot($e)
				->exists()) {
			Plot::fail('greenhouse');
			return;
		}

		if(\series\Place::model()
				->wherePlot($e)
				->exists()) {
			Plot::fail('deleteUsed');
			return;
		}

		Plot::model()->beginTransaction();

		Bed::model()
			->wherePlot($e)
			->delete();

		Plot::model()->delete($e);

		Plot::model()->commit();

	}

}
?>
