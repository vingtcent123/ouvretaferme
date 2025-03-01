<?php
namespace series;

class CultivationLib extends CultivationCrud {

	public static function getPropertiesCreate(): array {
		return ['plant', 'distance', 'density', 'rows', 'plantSpacing', 'rowSpacing', 'seedling', 'seedlingSeeds', 'yieldExpected', 'mainUnit', 'harvestPeriodExpected', 'harvestMonthsExpected', 'harvestWeeksExpected', 'sliceUnit', 'sliceTool', 'variety', 'actions'];
	}

	public static function getPropertiesUpdate(): array {
		return ['yieldExpected', 'mainUnit', 'unitWeight', 'bunchWeight', 'harvestPeriodExpected', 'harvestMonthsExpected', 'harvestWeeksExpected', 'plant', 'distance', 'density', 'rows', 'plantSpacing', 'rowSpacing', 'seedling', 'seedlingSeeds', 'sliceUnit', 'sliceTool', 'variety', 'actions'];
	}

	public static function getNew(Series $eSeries, \plant\Plant $ePlant): Cultivation {

		$eSeries->expects(['farm', 'season', 'area', 'length']);
		
		return new Cultivation([
			'farm' => $eSeries['farm'],
			'ccVariety' => \plant\VarietyLib::query($eSeries['farm'], $ePlant),
			'cTray' => \farm\ToolLib::getTraysByFarm($eSeries['farm']),
			'cSlice' => new \Collection(),
			'series' => $eSeries,
			'season' => $eSeries['season'],
			'sequence' => new \production\Sequence(),
			'sliceUnit' => Cultivation::PERCENT,
			'sliceTool' => new \farm\Tool(),
			'seedling' => NULL,
			'seedlingSeeds' => NULL,
			'distance' => Cultivation::SPACING,
			'density' => NULL,
			'rows' => NULL,
			'rowSpacing' => NULL,
			'plantSpacing' => NULL,
			'area' => $eSeries['area'],
			'length' => $eSeries['length'],
			'harvestPeriodExpected' => Cultivation::MONTH
		]);

	}

	public static function getBySeries(Series|\Collection $eSeries): \Collection {

		return Cultivation::model()
			->select(Cultivation::getSelection())
			->whereSeries($eSeries)
			->getCollection(NULL, NULL, 'id')
			->sort(function(Cultivation $e1, Cultivation $e2) {
				return \L::getCollator()->compare($e1['plant']['name'], $e2['plant']['name']);
			});

	}

	public static function getBySamePlants(\farm\Farm $eFarm, int $season, \plant\Plant $ePlant): \Collection {

		if($ePlant->empty()) {
			return new \Collection();
		}
		
		$cCultivation = Cultivation::model()
			->select([
				'id',
				'mainUnit', 'density',
				'startWeek', 'startAction',
				'cSlice' => SliceLib::delegateByCultivation(),
				'series' => [
					'name', 'area', 'use', 'mode', 'status',
					'cccPlace' => PlaceLib::delegateBySeries()
				],
				'harvestWeeks', 'harvestWeeksExpected',
				'firstTaskWeek' => self::delegateFirstTaskWeek($eFarm),
			])
			->whereFarm($eFarm)
			->whereSeason($season)
			->wherePlant($ePlant)
			->sort(['startWeek' => SORT_ASC])
			->getCollection()
			->filter(fn($eCultivation) => $eCultivation['series']['status'] === Series::OPEN);

		return $cCultivation;

	}

	public static function getForReport(\farm\Farm $eFarm, int $season, \plant\Plant $ePlant): \Collection {

		if($ePlant->empty()) {
			return new \Collection();
		}

		return Cultivation::model()
			->select([
				'id',
				'series' => ['name', 'mode', 'area'],
				'workingTimePlant' => Task::model()
					->group('cultivation')
					->delegateProperty('cultivation', new \Sql('SUM(time)', 'float')),
				'workingTimeShared' => new TimesheetModel()
					->select([
						'propertySource' => new \Sql('m3.id')
					])
					->join(Task::model(), 'm2.id = m1.task')
					->join(Series::model(), 'm3.id = m2.series')
					->where('m2.cultivation', NULL)
					->group(new \Sql('m3.id'))
					->delegateProperty('propertySource', new \Sql('SUM(m1.time / m3.plants)', 'float'), fn($value) => $value ?? 0, 'series'),
				'workingTime' => fn($e) => $e['workingTimePlant'] + $e['workingTimeShared'],
				'firstTaskWeek' => CultivationLib::delegateFirstTaskWeek($eFarm),
				'harvestedByUnit', 'harvestWeeks', 'harvestWeeksExpected'
			])
			->whereFarm($eFarm)
			->whereSeason($season)
			->wherePlant($ePlant)
			->sort(['startWeek' => SORT_ASC])
			->getCollection();

	}

	public static function getPlantsBySeason(\farm\Farm $eFarm, int $season): \Collection {

		return Cultivation::model()
			->select([
				'plant' => ['name']
			])
			->whereFarm($eFarm)
			->whereSeason($season)
			->group('plant')
			->getColumn('plant');

	}

	public static function getForArea(\farm\Farm $eFarm, int $season, \Search $search): \Collection {

		$ccCultivation = Cultivation::model()
			->select(Cultivation::getSelection())
			->select([
				'series' => [
					'cccPlace' => PlaceLib::delegateBySeries()
				],
				'cTask' => Task::model()
					->select([
						'cultivation', 'tools',
						'action' => \farm\Action::getSelection(),
						'plannedWeek', 'doneWeek',
						'status'
					])
					->whereAction('IN', \farm\ActionLib::getByFarm($eFarm, fqn: [ACTION_SEMIS_DIRECT, ACTION_SEMIS_PEPINIERE, ACTION_PLANTATION]))
					->sort(new \Sql('IF(status="'.\series\Task::TODO.'", plannedWeek, doneWeek), id'))
					->delegateCollection('cultivation'),
				'firstTaskWeek' => function($e) {

					if($e['cTask']->empty()) {
						return NULL;
					} else {
						$eTask = $e['cTask']->first();
						if($eTask['status'] === Task::TODO) {
							return $eTask['plannedWeek'];
						} else {
							return $eTask['doneWeek'];
						}
					}

				}
			])
			->whereFarm($eFarm)
			->whereSeason($season)
			->sort(['startWeek' => SORT_ASC, 'mainUnit' => SORT_ASC, 'id' => SORT_ASC])
			->getCollection(NULL, NULL, ['plant', NULL]);

		if($search->has('tool') and $search->get('tool')->notEmpty()) {

			$cultivationsTool = Task::model()
				->whereFarm($eFarm)
				->whereCultivation('IN', $ccCultivation)
				->where('JSON_CONTAINS(tools, \''.$search->get('tool')['id'].'\')')
				->getColumn('cultivation')
				->getIds();

			$ccCultivation->filter(fn($eCultivation) => in_array($eCultivation['id'], $cultivationsTool), depth: 2);

		}

		if($search->get('bedWidth')) {
			$ccCultivation->filter(fn($eCultivation) => ($eCultivation['series']['bedWidth'] === $search->get('bedWidth')), depth: 2);
		}

		self::orderByPlant($ccCultivation);

		return $ccCultivation;

	}

	public static function getForForecast(\farm\Farm $eFarm, int $season): \Collection {

		$cccCultivation = Cultivation::model()
			->select(Cultivation::getSelection())
			->whereFarm($eFarm)
			->whereSeason($season)
			->sort(['startWeek' => SORT_ASC, 'mainUnit' => SORT_ASC, 'id' => SORT_ASC])
			->getCollection(NULL, NULL, ['plant', 'mainUnit', NULL]);

		self::orderByPlant($cccCultivation);

		return $cccCultivation;

	}

	public static function getForSeedlingByStartWeek(\farm\Farm $eFarm, int $season, \Search $search): array {

		$cccCultivation = Cultivation::model()
			->select(Cultivation::getSelection())
			->whereSeedling($search->get('seedling'), if: $search->get('seedling'))
			->whereFarm($eFarm)
			->whereSeason($season)
			->sort(new \Sql('IF(startWeek IS NULL, 999, startWeek) DESC'))
			->getCollection(NULL, NULL, ['startWeek', 'plant', NULL]);

		$values = [];

		foreach($cccCultivation as $key => $ccCultivation) {
			$value = self::formatSeedling($ccCultivation, $search);
			if($value) {
				$values[$key] = $value;
			}
		}

		return $values;

	}

	public static function getForSeedling(\farm\Farm $eFarm, int $season, \Search $search): array {

		$ccCultivation = Cultivation::model()
			->select(Cultivation::getSelection())
			->whereSeedling($search->get('seedling'), if: $search->get('seedling'))
			->whereFarm($eFarm)
			->whereSeason($season)
			->getCollection(NULL, NULL, ['plant', NULL]);

		return self::formatSeedling($ccCultivation, $search);

	}

	protected static function formatSeedling(\Collection $ccCultivation, \Search $search): array {

		self::orderByPlant($ccCultivation);

		$eSupplier = $search->get('supplier');

		$values = [];

		foreach($ccCultivation as $cCultivation) {

			$seedsVariety = [];

			// On vérifie si c'est la saison du semis pour la culture
			$cCultivation->filter(function($eCultivation) {

				$eSeries = $eCultivation['series'];

					return (
						$eSeries['cycle'] === Series::ANNUAL or
						$eSeries['perennialSeason'] === 1
					);

			});

			if($cCultivation->empty()) {
				continue;
			}

			foreach($cCultivation as $eCultivation) {

				if($eCultivation['cSlice']->empty()) {

					if($eSupplier->empty()) {

						$eSlice = new Slice([
							'variety' => new \plant\Variety(),
							'partPercent' => 100
						]);

						self::calculateSeeds($eCultivation, $eSlice, $seedsVariety, FALSE);

					}

				} else {

					foreach($eCultivation['cSlice'] as $eSlice) {

						if(
							$eSupplier->empty() or
							(
								in_array($eCultivation['seedling'], [Cultivation::SOWING, Cultivation::YOUNG_PLANT]) and
								$eSlice['variety']['supplierSeed']->notEmpty() and $eSupplier['id'] === $eSlice['variety']['supplierSeed']['id']
							) or
							(
								$eCultivation['seedling'] === Cultivation::YOUNG_PLANT_BOUGHT and
								$eSlice['variety']['supplierPlant']->notEmpty() and $eSupplier['id'] === $eSlice['variety']['supplierPlant']['id']
							)
						) {
							self::calculateSeeds($eCultivation, $eSlice, $seedsVariety, $eSupplier->notEmpty());
						}

					}

				}

			}

			if($seedsVariety) {

				$values[] = [
					'plant' => $eCultivation['plant'],
					'seeds' => $seedsVariety
				];

			}

		}

		return $values;

	}

	protected static function calculateSeeds(Cultivation $eCultivation, Slice $eSlice, array &$seedsVariety, bool $removeIfEmpty) {

		$eCultivation->expects([
			'plant' => ['plantsSafetyMargin', 'seedsSafetyMargin']
		]);

		$eVariety = $eSlice['variety'];
		$varietyId = $eVariety->empty() ? '' : $eVariety['id'];

		$seeds = NULL;
		$youngPlantsProduced = NULL;
		$youngPlantsBought = NULL;
		$targeted = NULL;
		$error = NULL;

		switch($eCultivation['seedling']) {

			case Cultivation::SOWING :
				$seeds = $eCultivation->getSeeds($eSlice, $targeted, $eCultivation['plant']['seedsSafetyMargin'], error: $error);
				break;

			case Cultivation::YOUNG_PLANT :

				$seeds = $eCultivation->getSeeds($eSlice, $targeted, $eCultivation['plant']['plantsSafetyMargin']);
				$youngPlantsProduced = $eCultivation->getYoungPlants($eSlice, $targeted, $eCultivation['plant']['plantsSafetyMargin'], error: $error);
				break;

			case Cultivation::YOUNG_PLANT_BOUGHT :
				$youngPlantsBought = $eCultivation->getYoungPlants($eSlice, $targeted, error: $error);
				break;

			default :
				$error = 'seedling';

		}

		if($removeIfEmpty) {

			switch($eCultivation['seedling']) {

				case Cultivation::SOWING :
				case Cultivation::YOUNG_PLANT :
					if($seeds === 0) {
						return;
					}
					break;

				case Cultivation::YOUNG_PLANT_BOUGHT :
					if($youngPlantsBought === 0) {
						return;
					}
					break;

			}

		}


		if(empty($seedsVariety[$varietyId])) {

			$seedsVariety[$varietyId] = [
				'variety' => $eVariety,
				'error' => FALSE,
				'targeted' => FALSE,
				'seeds' => 0,
				'youngPlantsProduced' => 0,
				'youngPlantsBought' => 0,
				'cultivations' => []
			];

		}

		if($error) {
			$seedsVariety[$varietyId]['error'] = TRUE;
		}

		$seedsVariety[$varietyId]['cultivations'][] = [
			'series' => $eCultivation['series'],
			'cultivation' => $eCultivation,
			'slice' => $eSlice,
			'seeds' => $seeds,
			'error' => $error,
			'targeted' => $targeted,
			'youngPlantsProduced' => $youngPlantsProduced,
			'youngPlantsBought' => $youngPlantsBought
		];

		$seedsVariety[$varietyId]['seeds'] += $seeds;
		$seedsVariety[$varietyId]['youngPlantsProduced'] += $youngPlantsProduced;
		$seedsVariety[$varietyId]['youngPlantsBought'] += $youngPlantsBought;
		$seedsVariety[$varietyId]['targeted'] = ($targeted or $seedsVariety[$varietyId]['targeted']);

	}

	public static function fillSliceStats(Cultivation $eCultivation): void {

		if($eCultivation->empty()) {
			return;
		}

		$eCultivation->expects([
			'series',
			'cSlice',
			'plant' => ['seedsSafetyMargin', 'plantsSafetyMargin']
		]);

		if($eCultivation['cSlice']->empty()) {

			$eCultivation['cSlice']->append(new Slice([
				'variety' => new \plant\Variety(),
				'partPercent' => 100,
				'partArea' => $eCultivation['series']['area'],
				'partLength' => $eCultivation['series']['length']
			]));

		}

		$error = NULL;

		foreach($eCultivation['cSlice'] as $eSlice) {

			$eSlice['targeted'] = $eCultivation['series']->isTargeted();

			$eSlice['youngPlants'] = $eCultivation->getYoungPlants($eSlice, safetyMargin: match($eCultivation['seedling']) {
				Cultivation::YOUNG_PLANT => $eCultivation['plant']['plantsSafetyMargin'],
				default => 1
			}, error: $error);

			$eSlice['seeds'] = $eCultivation->getSeeds($eSlice, safetyMargin: match($eCultivation['seedling']) {
				Cultivation::SOWING => $eCultivation['plant']['seedsSafetyMargin'],
				Cultivation::YOUNG_PLANT => $eCultivation['plant']['plantsSafetyMargin'],
				default => 1
			});

			$eSlice['area'] = self::getArea($eCultivation['series'], $eCultivation, $eSlice);

		}

		$eCultivation['sliceError'] = $error;

	}

	public static function getArea(Series $eSeries, Cultivation $eCultivation, Slice $eSlice): ?int {

		$area = $eSeries['areaTarget'] ?? $eSeries['area'];

		if($area === NULL) {
			return NULL;
		}

		switch($eCultivation['sliceUnit']) {

			case Cultivation::PLANT :
				if($eCultivation['density'] === NULL) {
					return NULL;
				} else {
					return (int)($eSlice['partPlant'] / $eCultivation['density']);
				}

			case Cultivation::TRAY :
				if($eCultivation['density'] === NULL) {
					return NULL;
				} else {
					return (int)($eSlice['partTray'] * $eCultivation['sliceTool']['routineValue']['value'] / $eCultivation['density']);
				}

		}

		return match($eCultivation['sliceUnit']) {
			Cultivation::PERCENT => (int)($eSlice['partPercent'] / 100 * $area),
			Cultivation::LENGTH => ($eSeries['use'] === Cultivation::LENGTH) ? (int)($eSlice['partLength'] / ($eSeries['length'] ?? $eSeries['lengthTarget']) * $area) : NULL,
			Cultivation::AREA => ($eSeries['use'] === Cultivation::AREA) ? (int)($eSlice['partArea']) : NULL,
		};

	}

	public static function getForHarvesting(\farm\Farm $eFarm, int $season): \Collection {

		$eAction = \farm\ActionLib::getByFarm($eFarm, fqn: ACTION_RECOLTE);

		$ccCultivation = Cultivation::model()
			->select(Cultivation::getSelection())
			->select([
				'cTask' => Task::model()
					->select([
						'action' => ['fqn'],
						'minDoneWeek' => new \Sql('MIN(doneWeek)'),
						'maxDoneWeek' => new \Sql('MAX(doneWeek)'),
						'count' => new \Sql('COUNT(*)', 'int')
					])
					->whereAction($eAction)
					->whereStatus(Task::DONE)
					->group(['cultivation', 'action'])
					->delegateCollection('cultivation'),
				'firstTaskWeek' => self::delegateFirstTaskWeek($eFarm),
			])
			->whereFarm($eFarm)
			->whereSeason($season)
			->sort(['startWeek' => SORT_ASC, 'mainUnit' => SORT_ASC, 'id' => SORT_ASC])
			->getCollection(NULL, NULL, ['plant', NULL]);

		self::orderByPlant($ccCultivation);

		foreach($ccCultivation as $cCultivation) {

			foreach($cCultivation as $eCultivation) {

				$harvesting = [
					'nHarvest' => 0,
					'firstHarvest' => NULL,
					'lastHarvest' => NULL
				];

				foreach($eCultivation['cTask'] as $eTask) {

					$harvesting['nHarvest'] = round((strtotime($eTask['maxDoneWeek']) - strtotime($eTask['minDoneWeek'])) / 86400 / 7) + 1;
					$harvesting['firstHarvest'] = strtotime($eTask['minDoneWeek']);
					$harvesting['lastHarvest'] = strtotime($eTask['maxDoneWeek']);

				}

				$eCultivation['harvesting'] = $harvesting;

			}

		}

		return $ccCultivation;

	}

	public static function getPaceByFarm(\farm\Farm $eFarm, int $season, \Collection $cAction): \Collection {

		$ccCultivation = \series\CultivationLib::getWorkingTimeByFarm($eFarm, $season, new \Search([
			'cAction' => $cAction
		]));

		$cPlant = new \Collection();

		foreach($ccCultivation as $cCultivation) {

			$ePlant = $cCultivation->first()['plant'];

			$cTask = new \Collection();
			$cTaskHarvested = new \Collection();

			$area = 0;

			foreach($cCultivation as $eCultivation) {

				$eSeries = $eCultivation['series'];

				$area += $eSeries['area'];

				foreach($eCultivation['cTask'] as $eTask) {

					if($eTask['totalTime'] === NULL) {
						continue;
					}

					$eAction = $eTask['action'];

					if($cTask->offsetExists($eAction['id']) === FALSE) {
						$cTask[$eAction['id']] = $eTask;
						$cTask[$eAction['id']]['area'] = 0;
						$cTask[$eAction['id']]['plants'] = 0;
					} else {
						$cTask[$eAction['id']]['totalTime'] += $eTask['totalTime'];
					}

					$cTask[$eAction['id']]['area'] += $eSeries['area'];
					$cTask[$eAction['id']]['plants'] += $eCultivation['density'] * $eSeries['area'];

				}

				foreach($eCultivation['cTaskHarvested'] as $eTaskHarvested) {

					$unit = $eTaskHarvested['harvestUnit'];

					if($unit === NULL) {
						continue;
					}

					if($cTaskHarvested->offsetExists($unit) === FALSE) {
						$cTaskHarvested[$unit] = new Task([
							'harvestUnit' => $unit,
							'totalHarvested' => 0,
							'totalTime' => 0,
						]);
					}

					$cTaskHarvested[$unit]['totalHarvested'] += $eTaskHarvested['totalHarvested'];
					$cTaskHarvested[$unit]['totalTime'] += $eTaskHarvested['totalTime'];

				}

			}

			if($area === 0) {
				continue;
			}

			$ePlant['area'] = $area;
			$ePlant['cTask'] = $cTask;
			$ePlant['cTaskHarvested'] = $cTaskHarvested;

			$cPlant[$ePlant['id']] = $ePlant;

		}

		return $cPlant;

	}

	public static function getWorkingTimeByFarm(\farm\Farm $eFarm, int $season, \Search $search): \Collection {

		$ccCultivation = Cultivation::model()
			->select(Cultivation::getSelection() + [
				'firstTaskWeek' => self::delegateFirstTaskWeek($eFarm),
			])
			->whereFarm($eFarm)
			->whereSeason($season)
			->sort(['startWeek' => SORT_ASC, 'mainUnit' => SORT_ASC, 'id' => SORT_ASC])
			->getCollection(NULL, NULL, ['plant', NULL]);

		self::orderByPlant($ccCultivation);

		$cSeries = $ccCultivation->getColumnCollection('series');

		if($search->has('cAction')) {
			Task::model()->whereAction('IN', $search->get('cAction'));
		}

		if(
			$search->has('action') and
			$search->get('action')->notEmpty()
		) {
			Task::model()->whereAction($search->get('action'));
		}

		$cccTask = Task::model()
			->select([
				'series',
				'cultivation',
				'action' => \farm\Action::getSelection(),
				'totalTime' => new \Sql('SUM(time)', 'float')
			])
			->group(['series', 'cultivation', 'action'])
			->whereSeries('IN', $cSeries)
			->sort(new \Sql('totalTime DESC'))
			->getCollection(index: ['series', 'cultivation', NULL]);

		$cccTaskHarvested = TaskLib::getHarvestedBySeriesCollection($cSeries, $eFarm);

		foreach($ccCultivation as $cCultivation) {

			foreach($cCultivation as $eCultivation) {

				$eSeries = $eCultivation['series'];
				$ccTask = $cccTask[$eSeries['id']] ?? new \Collection();
				$cTaskHarvested = $cccTaskHarvested[$eSeries['id']][$eCultivation['id']] ?? new \Collection();

				$cTaskPlant = $ccTask[$eCultivation['id']] ?? new \Collection();
				$cTaskSoil = $ccTask[NULL] ?? new \Collection();

				$eCultivation['totalTimePlant'] = $cTaskPlant->sum('totalTime');
				$eCultivation['totalTimeSoil'] = round($cTaskSoil->sum('totalTime') / $eSeries['plants'], 2);

				$cTask = new \Collection();

				foreach($cTaskPlant as $eTask) {
					$cTask[] = new Task([
						'action' => $eTask['action'],
						'totalTime' => $eTask['totalTime']
					]);
				}

				foreach($cTaskSoil as $eTask) {
					$cTask[] = new Task([
						'action' => $eTask['action'],
						'totalTime' => round($eTask['totalTime'] / $eSeries['plants'], 2)
					]);
				}

				$cTask->sort(['totalTime' => SORT_DESC]);

				$eCultivation['cTask'] = $cTask;
				$eCultivation['cTaskHarvested'] = $cTaskHarvested;

			}

		}

		return $ccCultivation;

	}

	public static function orderByPlant(\Collection $cxCultivationInput, \Collection $cPlantPriority = new \Collection()): void {

		$priorities = [];

		if($cPlantPriority->notEmpty()) {
			$position = 0;
			foreach($cPlantPriority as $ePlantPriority) {
				$priorities[$ePlantPriority['id']] = $position++;
			}
		}

		$cPlant = $cxCultivationInput->getColumn('plant', 'plant');

		$cxCultivationInput->uksort(function($a, $b) use ($cPlant, $priorities) {

			if($priorities !== []) {

				$positionA = $priorities[$cPlant[$a]['id']] ?? NULL;
				$positionB = $priorities[$cPlant[$b]['id']] ?? NULL;

				if($positionA === NULL and $positionB === NULL) {

				} else if($positionA === NULL) {
					return 1;
				} else if($positionB === NULL) {
					return -1;
				} else {
					return ($positionA < $positionB) ? -1 : 1;
				}

			}

			return \L::getCollator()->compare($cPlant[$a]['name'], $cPlant[$b]['name']);

		});

	}

	public static function delegateFirstTaskWeek(\farm\Farm $eFarm): TaskModel {

		return Task::model()
			->whereAction('IN', \farm\ActionLib::getByFarm($eFarm, fqn: [ACTION_SEMIS_DIRECT, ACTION_SEMIS_PEPINIERE, ACTION_PLANTATION]))
			->group('cultivation')
			->delegateProperty('cultivation', new \Sql('MIN(IF(status="'.\series\Task::TODO.'", plannedWeek, doneWeek))'));

	}

	public static function delegateBySeries(?array $select = NULL): CultivationModel {

		return (clone Cultivation::model())
			->select($select ?? Cultivation::getSelection())
			->delegateCollection('series', 'id');

	}

	public static function canDelete(Cultivation $eCultivation): bool {

		$eCultivation->expects('series');

		return Cultivation::model()
			->whereSeries($eCultivation['series'])
			->count() > 1;

	}

	public static function create(Cultivation $e): void {

		$e->expects([
			'series' => ['area'],
			'season', 'farm',
			'cSlice', 'actions'
		]);

		Cultivation::model()->beginTransaction();

		\production\SliceLib::createVariety($e['cSlice']);

		$e['area'] = $e['series']['area'];
		$e['length'] = $e['series']['length'];

		\production\CropLib::calculateDistance($e, $e['series']);

		try {

			parent::create($e);

			self::createTasks($e);

			$fw = new \FailWatch();

			// Ajout de la répartition des variétés
			SliceLib::createCollection($e['cSlice']);

			if($fw->ok()) {

				Series::model()->update($e['series'], [
					'plants' => new \Sql('plants + 1')
				]);

				SeriesLib::recalculate($e['farm'], $e['series']);

				Cultivation::model()->commit();

			} else {
				Cultivation::model()->rollBack();
			}

		} catch(\DuplicateException) {

			Cultivation::fail('plant.duplicate');

			Cultivation::model()->rollBack();

		}

	}

	public static function createTasks(Cultivation $e): void {

		$e->expects(['actions']);

		$cAction = \farm\ActionLib::getByFarm($e['farm'], fqn: array_keys($e['actions']), index: 'fqn');
		$eCategory = \farm\CategoryLib::getByFarm($e['farm'], fqn: CATEGORIE_CULTURE);

		foreach($e['actions'] as $action => $week) {

			$eAction = $cAction[$action];

			$tools = [];

			if(
				$action === ACTION_SEMIS_PEPINIERE and
				$e['sliceTool']->notEmpty()
			) {
				$tools[] = $e['sliceTool']['id'];
			}

			$eTask = new \series\Task([
				'farm' => $e['farm'],
				'action' => $eAction,
				'category' => $eCategory,
				'series' => $e['series'],
				'cultivation' => $e,
				'plant' => $e['plant'],
				'plannedWeek' => $week,
				'status' => Task::TODO,
				'repeatMaster' => new Repeat(),
				'tools' => $tools
			]);

			TaskLib::create($eTask);

		}

	}

	public static function buildFromSequence(\production\Sequence $eSequence, \farm\Farm $eFarm, int $season): \Collection {

		$eSequence->expects([
			'id', 'cycle', 'cCrop'
		]);

		// Série minimale
		$eSeries = new Series([
			'farm' => $eFarm,
			'season' => $season,
			'cycle' => $eSequence['cycle'],
			'use' => $eSequence['use'],
			'length' => NULL,
			'lengthTarget' => NULL,
			'area' => 0,
			'areaTarget' => 0,
		]);

		// Construction des cultivations
		$cCultivation = new \Collection();

		foreach($eSequence['cCrop'] as $eCrop) {

			$eCultivation = new Cultivation([
				'sequence' => $eSequence,
				'series' => $eSeries,
				'season' => $season,
				'crop' => $eCrop,
				'plant' => $eCrop['plant'],
				'sliceUnit' => Cultivation::PERCENT,
				'sliceTool' => new \farm\Tool(),
				'distance' => $eCrop['distance'],
				'density' => $eCrop['density'],
				'rows' => $eCrop['rows'],
				'rowSpacing' => $eCrop['rowSpacing'],
				'plantSpacing' => $eCrop['plantSpacing'],
				'seedling' => $eCrop['seedling'],
				'seedlingSeeds' => $eCrop['seedlingSeeds'],
				'yieldExpected' => $eCrop['yieldExpected'],
				'mainUnit' => $eCrop['mainUnit'],
				'ccVariety' => \plant\VarietyLib::query($eFarm, $eCrop['plant']),
				'cSlice' => $eCrop['cSlice']
			]);

			$cCultivation[$eCrop['id']] = $eCultivation;

		}

		return $cCultivation;

	}

	public static function buildHarvestsFromSequence(\Collection $cCultivation, \Collection $cFlow, int $referenceYear): \Collection {

		$harvests = \production\CropLib::getHarvestsFromFlow($cFlow, $referenceYear);

		foreach($cCultivation as $eCultivation) {

			$harvestWeeksExpected = $harvests[$eCultivation['plant']['id']] ?? [];

			if($harvestWeeksExpected) {
				$eCultivation['harvestPeriodExpected'] = Cultivation::WEEK;
				$eCultivation['harvestMonthsExpected'] = \util\DateLib::convertWeeksToMonths($harvestWeeksExpected);
				$eCultivation['harvestWeeksExpected'] = $harvestWeeksExpected;
			} else {
				$eCultivation['harvestPeriodExpected'] = Cultivation::MONTH;
				$eCultivation['harvestMonthsExpected'] = NULL;
				$eCultivation['harvestWeeksExpected'] = NULL;
			}

		}

		return $cCultivation;

	}

	public static function update(Cultivation $e, array $properties): void {

		Cultivation::model()->beginTransaction();

		$key = array_search('variety', $properties);

		if($key !== FALSE) {

			\production\SliceLib::createVariety($e['cSlice']);

			SliceLib::deleteByCultivation($e);
			SliceLib::createCollection($e['cSlice']);

			unset($properties[$key]);

		}

		$harvestedNormalizedUpdate = array_intersect(['mainUnit', 'bunchWeight', 'unitWeight'], $properties);

		if($harvestedNormalizedUpdate) {
			$e->calculateHarvestedNormalized();
			$properties[] = 'harvestedNormalized';
		}

		$distanceUpdate = array_intersect(['distance', 'density', 'rows', 'rowSpacing', 'plantSpacing'], $properties);

		if(count($distanceUpdate) === 5) {
			\production\CropLib::calculateDistance($e, $e['series']);
		} else if(count($distanceUpdate) > 0) {
			throw new \Exception('Properties must be updated together');
		}

		if(array_delete($properties, 'actions')) {
			self::createTasks($e);
		}

		parent::update($e, $properties);

		if(in_array('harvestWeeksExpected', $properties)) {
			SeriesLib::recalculate($e['farm'], $e['series']);
		}

		// Mise à jour de l'espèce -> mettre à jour également les tâches
		if(in_array('plant', $properties)) {

			Task::model()
				->whereCultivation($e)
				->update([
					'plant' => $e['plant']
				]);

			Timesheet::model()
				->whereSeries($e['series'])
				->whereCultivation($e)
				->update([
					'plant' => $e['plant']
				]);

		}

		Cultivation::model()->commit();

	}

	public static function updateDensityBySeries(Series $eSeries): void {

		$eSeries->expects(['bedWidth', 'alleyWidth']);

		Cultivation::model()
			->whereSeries($eSeries)
			->whereDistance(Cultivation::SPACING)
			->update([
				'density' => match($eSeries['use']) {
					Series::BLOCK => new \Sql('IF(rowSpacing IS NOT NULL AND plantSpacing IS NOT NULL, ROUND(100 / rowSpacing * 100 / plantSpacing * 10) / 10, NULL)'),
					Series::BED => new \Sql('IF('.Cultivation::model()->field('rows').' IS NOT NULL AND plantSpacing IS NOT NULL, '.Cultivation::model()->field('rows').' / ('.($eSeries['bedWidth'] + $eSeries['alleyWidth'] ?? 0).' / 100) * 100 / plantSpacing, NULL)')
				}
			]);

	}

	public static function delete(Cultivation $e): void {

		$e->expects(['series']);

		Cultivation::model()->beginTransaction();

		if(self::canDelete($e) === FALSE) {
			Cultivation::fail('canNotDelete');
			return;
		}

		$cTask = Task::model()
			->select(Task::getSelection())
			->whereCultivation($e)
			->getCollection();

		TaskLib::deleteCollection($cTask);

		Slice::model()
			->whereCultivation($e)
			->delete();

		// Supprime les rapports
		\analyze\Cultivation::model()
			->whereCultivation($e)
			->delete();

		Cultivation::model()->delete($e);

		Series::model()->update($e['series'], [
			'plants' => new \Sql('plants - 1')
		]);

		// Mets à jour le compteur de productions
		$cCultivation = self::getBySeries($e['series']);

		$e['series']['plants'] = $cCultivation->count();

		Series::model()
			->select('plants')
			->update($e['series']);

		// on passe les actions générales sur la culture restante
		if($e['series']['plants'] === 1) {

			$eCultivationRemaining = $cCultivation->first();

			Task::model()
				->whereSeries($e['series'])
				->whereCultivation(NULL)
				->update([
					'cultivation' => $eCultivationRemaining,
					'plant' => $eCultivationRemaining['plant']
				]);

			Timesheet::model()
				->whereSeries($e['series'])
				->whereCultivation(NULL)
				->update([
					'cultivation' => $eCultivationRemaining,
					'plant' => $eCultivationRemaining['plant']
				]);

		}

		Cultivation::model()->commit();

	}

}
?>
