<?php
namespace series;

class TaskLib extends TaskCrud {

	public static function getPropertiesCreate(): \Closure {
		return function(Task $e) {

			$e['category']->expects(['fqn']);

			$properties = TaskLib::getPropertiesWrite($e);
			$properties[] = 'harvestUnit';

			if($e['category']['fqn'] === CATEGORIE_CULTURE) {

				if($e['series']->empty()) {
					$properties[] = 'plant';
				}

				$properties[] = 'variety';

			}

			$properties[] = 'repeatMaster';

			return $properties;

		};
	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Task $e) {

			$e->expects([
				'status',
				'series',
				'action' => ['fqn']
			]);

			$properties = TaskLib::getPropertiesWrite($e);
			
			// On ne peut pas changer l'action si c'est une récolte
			if($e['action']['fqn'] === ACTION_RECOLTE) {
				array_delete($properties, 'action');
			} else {

				// On ne peut changer de culture que si ce n'est pas une récolte
				if($e['series']->notEmpty()) {
					$properties[] = 'cultivation';
				}

			}

			$properties[] = 'variety';

			return $properties;

		};

	}

	public static function getPropertiesWrite(Task $e): array {

		$e->expects(['status', 'series', 'category']);

		$properties = ['action', 'description', 'harvestQuality', 'fertilizer', 'toolsList'];

		switch($e['status']) {

			case Task::TODO :
				$properties[] = 'planned';
				$properties[] = 'timeExpected';
				break;

			case Task::DONE :
				$properties[] = 'done';
				break;

		}

		return $properties;

	}

	public static function getTools(Task $e): \Collection {

		return Requirement::model()
			->select([
				'tool' => \farm\Tool::getSelection()
			])
			->whereTask($e)
			->getColumn('tool')
			->sort('name', natural: TRUE);

	}

	public static function getByFarm(\farm\Farm $eFarm, ?int $page = NULL, \Search $search = new \Search()): array {

		if($search->get('year')) {

			if($search->get('month')) {
				Task::model()->where('"'.$search->get('year').''.sprintf('%02d', $search->get('month')).'" BETWEEN EXTRACT(YEAR_MONTH FROM timesheetStart) AND EXTRACT(YEAR_MONTH FROM timesheetStop)');
			} else {
				Task::model()->where($search->get('year').' BETWEEN EXTRACT(YEAR FROM timesheetStart) AND EXTRACT(YEAR FROM timesheetStop)');
			}

		}

		if($search->get('week')) {

			$weekYear = week_year($search->get('week'));
			$weekNumber = week_number($search->get('week'));

			Task::model()
				->where($weekYear.' BETWEEN EXTRACT(YEAR FROM timesheetStart) AND EXTRACT(YEAR FROM timesheetStop)')
				->where('WEEK(timesheetStart, 1) = '.$weekNumber);

		}

		if($search->get('user')) {
			Task::model()
				->where('m1.farm', $eFarm)
				->where('m2.user', $search->get('user'))
				->join(Timesheet::model(), 'm2.task = m1.id');
			$index = ['id'];
		} else {
			Task::model()->whereFarm($eFarm);
			$index = NULL;
		}

		if($page === NULL) {
			$limit = NULL;
			$position = NULL;
		} else {
			$limit = 100;
			$position = $page * $limit;
		}

		$cTask = Task::model()
			->select(Task::getSelection())
			->select([
				'series' => [
					'cccPlace' => PlaceLib::delegateBySeries()
				],
				'cccPlace' => PlaceLib::delegateByTask(),
				'plant' => ['name'],
				'times' => TimesheetLib::delegateByTask()
			])
			->whereSeries($search->get('series'), if: $search->get('series'))
			->wherePlant($search->get('plant'), if: $search->get('plant'))
			->whereAction($search->get('action'), if: $search->get('action'))
			->whereCategory($search->get('category'), if: $search->get('category'))
			->whereStatus($search->get('status'), if: $search->get('status'))
			->where('time > 0', if: $search->get('time'))
			->option('count')
			->sort(new \Sql('IF(status = "'.Task::TODO.'", IF(plannedWeek IS NOT NULL, plannedWeek, "0000-00-00"), NULL) DESC, IF(timesheetStop IS NOT NULL, timesheetStop, updatedAt) DESC'))
			->getCollection($position, $limit, $index);

		return [$cTask, Task::model()->found()];

	}

	public static function getBySeries(Series $eSeries) {

		return Task::model()
			->select([
				'id',
				'cultivation', 'series', 'farm',
				'plannedWeek', 'doneWeek', 'plannedUsers',
				'display' => new \Sql('IF(doneWeek IS NULL, plannedWeek, doneWeek)', 'string'),
				'cComment' => CommentLib::delegateByTask(),
				'cRequirement' => Requirement::model()
					->select([
						'tool' => ['name', 'vignette']
					])
					->delegateCollection('task'),
				'description', 'fertilizer', 'time', 'harvest', 'harvestUnit',
				'harvestQuality' => ['name'],
				'action' => ['fqn', 'name', 'color'],
				'plant',
				'variety' => ['name'],
				'createdAt', 'doneDate',
				'status',
				'times' => TimesheetLib::delegateByTask()
			])
			->whereSeries($eSeries)
			->sort(new \Sql('display ASC'))
			->getCollection(NULL, NULL);

	}

	public static function calculateWorkingTimeForReport(\farm\Farm $eFarm, int $year, \plant\Plant $ePlant): float {

		return Task::model()
			->join(Timesheet::model(), 'm2.task = m1.id')
			->where('m1.farm', '=', $eFarm)
			->where('m1.plant', '=', $ePlant)
			->where('m1.series', '=', NULL)
			->where('m2.date', 'LIKE', $year.'-%')
			->getValue(new \Sql('SUM(m2.time)', 'float')) ?? 0.0;

	}

	public static function getByYear(\farm\Farm $eFarm, int $year, \Search $search): \Collection {

		self::applySearch($search);

		$ccTask = Task::model()
			->select(Task::getSelection())
			->select([
				'cccPlace' => PlaceLib::delegateByTask(),
				'series' => [
					'cccPlace' => PlaceLib::delegateBySeries()
				],
				'display' => new \Sql('IF(doneWeek IS NULL, plannedWeek, doneWeek)'),
				'plant' => ['name'],
				'cultivation' => ['seedlingSeeds', 'startWeek', 'startAction'],
				'times' => TimesheetLib::delegateByTask()
			])
			->whereFarm($eFarm)
			->wherePlannedWeek('LIKE', $year.'-%')
			->sort([
				'plannedWeek' => SORT_ASC,
				'createdAt' => SORT_ASC
			])
			->getCollection(NULL, NULL, ['display', NULL]);

		$delete = [];

		foreach($ccTask as $key => $cTask) {
			self::filterPlot($search, $cTask);
			if($cTask->empty()) {
				$delete[] = $key;
			}
		}

		foreach($delete as $key) {
			$ccTask->offsetUnset($key);
		}

		return $ccTask;

	}

	protected static function applySearch(\Search $search): void {

		if($search->get('action') and $search->get('action')->notEmpty()) {
			Task::model()->whereAction($search->get('action'));
		}

		if($search->get('plant') and $search->get('plant')->notEmpty()) {
			Task::model()->wherePlant($search->get('plant'));
		}

	}

	public static function getForDaily(\farm\Farm $eFarm, string $week, \user\User $eUser, \farm\Action $eActionHarvest, \Search $search = new \Search()): array {

		$ccTimesheet = TimesheetLib::getTimesheetsByDaily($eFarm, $week, $eUser);

		$cccTaskByDate = (new \Collection())->setDepth(3);

		foreach(week_dates($week) as $date) {
			$cccTaskByDate[$date] = (new \Collection())->setDepth(2);
		}

		$firstDate = week_date_starts($week);
		$lastDate = week_date_ends($week);

		Task::model()
			->select([
				'cTimesheet' => Timesheet::model()
					->select([
						'date', 'user',
						'totalTime' => new \Sql('SUM(time)', 'float')
					])
					->whereFarm($eFarm)
					->whereUser($eUser, if: $eUser->notEmpty())
					->whereDate('BETWEEN', new \Sql(Task::model()->format($firstDate).' AND '.Task::model()->format($lastDate)))
					->group(['task', 'user', 'date'])
					->delegateCollection('task', ['date', 'user'])
			])
			->or(
				fn() => $this->whereId('IN', $ccTimesheet->getColumn('task')),
				fn() => $this
					->where(fn() => 'JSON_CONTAINS(plannedUsers, \''.$eUser['id'].'\')', if: $eUser->notEmpty())
					->or(
						fn() => $this->wherePlannedWeek($week),
						fn() => $this->whereDoneWeek($week)
					)
			);

		$ccTask = self::getForPlanning($eFarm, $search, eUser: $eUser);

		if($ccTask->empty()) {
			return [
				$ccTimesheet,
				$cccTaskByDate
			];
		}

		// Remplissage de la récolte
		if($ccTask->offsetExists($eActionHarvest['id'])) {
			\series\TaskLib::fillHarvestDates($ccTask[$eActionHarvest['id']]);
		}

		foreach($cccTaskByDate as $date => $ccTaskByDate) {

			$tasksByDate = array_flip(($ccTimesheet[$date] ?? new \Collection())->getColumnCollection('task')->getColumn('id'));

			foreach($ccTask as $action => $cTask) {

				foreach($cTask as $eTask) {

					$in = (
						$eTask['plannedDate'] === $date or
						$eTask['doneDate'] === $date
					);

					if(
						$in or
						array_key_exists($eTask['id'], $tasksByDate)
					) {

						$eTaskNew = clone $eTask;
						$eTaskNew['times'] = ($eTask['cTimesheet'][$date] ?? new \Collection())->makeArray(function(Timesheet $e, &$key) {

							$key = $e['user']['id'];
							return $e['totalTime'];

						});

						if($in or $eTaskNew['times']) {

							$cccTaskByDate[$date][$action] ??= new \Collection();
							$cccTaskByDate[$date][$action][] = $eTaskNew;

						}

					}

				}

			}

		}

		return [
			$ccTimesheet,
			$cccTaskByDate,
		];

	}

	public static function getForAssign(\farm\Farm $eFarm, string $week): \Collection {

		Task::model()->wherePlannedDate(NULL);
		$ccTaskTodo = self::getForWeekTodo($eFarm, $week);

		$cccTask = new \Collection([
			'todo' => $ccTaskTodo,
			'delayed' => self::getForWeekDelayed($eFarm, $week),
			'unplanned' => self::getByUnplanned($eFarm, $week),
		]);

		$cccTask->setDepth(3);

		return $cccTask;

	}

	public static function getForWeek(\farm\Farm $eFarm, string $week, \farm\Action $eActionHarvest, \Search $search): \Collection {

		$ccTaskDone = self::getForWeekDone($eFarm, $week, $search);

		$harvestId = \farm\ActionLib::getByFarm($eFarm, fqn: ACTION_RECOLTE)['id'];

		if($ccTaskDone->offsetExists($harvestId)) {
			$cTaskHarvested = $ccTaskDone[$harvestId];
			$ccTaskDone->offsetUnset($harvestId);
		} else {
			$cTaskHarvested = new \Collection();
		}

		$cccTask = new \Collection([
			'todo' => self::getForWeekTodo($eFarm, $week, $search),
			'delayed' => self::getForWeekDelayed($eFarm, $week, $search),
			'unplanned' => self::getByUnplanned($eFarm, $week, $search),
			'done' => $ccTaskDone,
			'harvested' => $cTaskHarvested,
		]);

		$cccTask->setDepth(3);

		foreach($cccTask as $type => $ccTask) {

			if($type !== 'harvested') {

				if($ccTask->offsetExists($eActionHarvest['id'])) {
					$cTask = $ccTask[$eActionHarvest['id']];
				} else {
					continue;
				}

			} else {
				$cTask = $ccTask;
			}

			// Mise à jour de la quantité récoltée par la quantité récoltée ce jour
			\series\TaskLib::fillHarvestWeeks($cTask);
			\series\TaskLib::fillHarvestByWeek($cTask, $week);

		}

		return $cccTask;

	}

	public static function getByWeekAndAction(\farm\Farm $eFarm, string $week, \farm\Action $eAction): \Collection {

		Task::model()
			->select([
				'cRequirement' => Requirement::model()
					->select([
						'tool' => \farm\Tool::getSelection()
					])
					->delegateCollection('task'),
				'cultivation' => [
					'cSlice' => SliceLib::delegateByCultivation(),
				]
			])
			->whereAction($eAction)
			->or(
				fn() => $this
					->whereStatus(Task::TODO)
					->wherePlannedWeek($week),
				fn() => $this
					->whereStatus(Task::DONE)
					->whereDoneWeek($week)
			);

		return self::getForPlanning($eFarm, indexByAction: FALSE)
			->sort(function(Task $e1, Task $e2) {
				return \L::getCollator()->compare(
					$e1['series']->empty() ? '' : $e1['series']['name'],
					$e2['series']->empty() ? '' : $e2['series']['name']
				);
			});

	}

	public static function getForWeekTodo(\farm\Farm $eFarm, string $week, \Search $search = new \Search()): \Collection {

		Task::model()
			->wherePlannedWeek($week)
			->whereStatus(Task::TODO);

		return self::getForPlanning($eFarm, $search, timesheetWeek: $week);

	}

	public static function getByUnplanned(\farm\Farm $eFarm, string $referenceWeek, \Search $search = new \Search()): \Collection {

		Task::model()
			->wherePlannedWeek(NULL)
			->whereStatus(Task::TODO);

		return self::getForPlanning($eFarm, $search, timesheetWeek: $referenceWeek);

	}

	public static function getForWeekDone(\farm\Farm $eFarm, string $week, \Search $search = new \Search()): \Collection {

		$cTaskTimesheet = TimesheetLib::getTasksByWeek($eFarm, $week);
		$cTaskHarvest = HarvestLib::getTasksByWeek($eFarm, $week);

		Task::model()
			->or(
				fn() => $this->whereId('IN', $cTaskTimesheet),
				fn() => $this->whereId('IN', $cTaskHarvest),
				fn() => $this
					->whereStatus(Task::DONE)
					->whereDoneWeek($week),
			);

		return self::getForPlanning($eFarm, $search, timesheetWeek: $week);

	}

	public static function getForWeekDelayed(\farm\Farm $eFarm, string $referenceWeek, \Search $search = new \Search()): \Collection {

		// Pour une référence dans le futur, on n'affiche pas les retards
		if(strcmp($referenceWeek, currentWeek()) > 0) {
			return new \Collection();
		}

		Task::model()
			->wherePlannedWeek('<', $referenceWeek)
			->whereStatus(Task::TODO)
			->sort([
				'plannedWeek' => SORT_ASC,
				'createdAt' => SORT_ASC
			]);

		return self::getForPlanning($eFarm, $search, timesheetWeek: $referenceWeek);

	}

	public static function getHarvestedByCultivation(Cultivation $eCultivation): \Collection {

		$eCultivation->expects(['cSlice', 'farm']);

		$eAction = \farm\ActionLib::getByFarm($eCultivation['farm'], fqn: ACTION_RECOLTE);

		$cTask = Task::model()
			->select([
				'variety' => ['name'],
				'totalHarvest' => new \Sql('SUM(harvest)', 'float'),
				'harvestUnit',
				'harvestQuality' => ['name', 'yield']
			])
			->group(['variety', 'harvestQuality', 'harvestUnit'])
			->whereAction($eAction)
			->whereCultivation($eCultivation)
			->sort(['variety' => SORT_ASC, 'harvestQuality' => SORT_ASC, 'harvestUnit' => SORT_ASC])
			->having('totalHarvest > 0')
			->getCollection();

		return $cTask;

	}

	protected static function getForPlanning(\farm\Farm $eFarm, \Search $search = new \Search(), bool $indexByAction = TRUE, \user\User $eUser = new \user\User(), ?string $timesheetWeek = NULL, ?string $timesheetDate = NULL): \Collection {

		$index = $indexByAction ? ['action', NULL] : NULL;

		self::applySearch($search);

		$ccTask = Task::model()
			->select(Task::getSelection())
			->select([
				'cccPlace' => PlaceLib::delegateByTask(),
				'series' => [
					'cccPlace' => PlaceLib::delegateBySeries()
				],
				'cComment' => CommentLib::delegateByTask(),
				'plant' => ['name'],
				'cultivation' => ['startWeek', 'startAction', 'seedling', 'seedlingSeeds', 'distance', 'plantSpacing', 'rowSpacing', 'rows', 'density', 'sliceUnit'],
			])
			->select([
				'times' => TimesheetLib::delegateByTask($eUser, $timesheetWeek, $timesheetDate)
			], if: $timesheetWeek !== NULL or $timesheetDate !== NULL)
			->whereFarm($eFarm)
			->sort([
				'harvestQuality' => SORT_ASC,
				'plant' => SORT_ASC,
				'variety' => SORT_ASC,
				'id' => SORT_ASC
			])
			->getCollection(index: $index);

		if($indexByAction) {

			$ccTask->sort(function(\Collection $c1, \Collection $c2) {

				return \L::getCollator()->compare(
					$c1->first()['action']['name'],
					$c2->first()['action']['name']
				);

			});

			foreach($ccTask as $cTask) {

				$cTask->sort(function(Task $e1, Task $e2) {

					return \L::getCollator()->compare(
						$e1['plant']['name'] ?? '',
						$e2['plant']['name'] ?? ''
					);

				});

			}

			$delete = [];

			foreach($ccTask as $key => $cTask) {
				self::filterPlot($search, $cTask);
				if($cTask->empty()) {
					$delete[] = $key;
				}
			}

			foreach($delete as $key) {
				$ccTask->offsetUnset($key);
			}

		} else {
			self::filterPlot($search, $ccTask);
		}

		return $ccTask;

	}

	private static function filterPlot(\Search $search, \Collection $cTask): void {

		$delete = [];
		$ePlotSearch = $search->get('plot');

		if($ePlotSearch !== NULL) {

			foreach($cTask as $key => $eTask) {

				if($eTask['series']->notEmpty()) {
					$cccPlace = $eTask['series']['cccPlace'];
				} else {
					$cccPlace = $eTask['cccPlace'];
				}

				if(
					$cccPlace->empty() or
					$cccPlace->find(fn($ePlace) => $ePlace['plot']['id'] === $ePlotSearch['id'], depth: 3, limit: 1)->empty()
				) {
					$delete[] = $key;
				}

			}

		}

		foreach($delete as $key) {
			$cTask->offsetUnset($key);
		}

	}

	public static function getWorkingTimeBySeries(Series $eSeries): \Collection {

		return Task::model()
			->select([
				'cultivation',
				'action' => ['fqn', 'color', 'pace', 'name'],
				'totalTime' => new \Sql('SUM(time)', 'float')
			])
			->whereSeries($eSeries)
			->group(['cultivation', 'action'])
			->having(new \Sql('totalTime IS NOT NULL'))
			->sort([
				'cultivation' => SORT_ASC,
				'totalTime' => SORT_DESC
			])
			->getCollection(index: ['cultivation', NULL]);

	}

	public static function getHarvestedBySeries(Series $eSeries): \Collection {

		$eSeries->expects(['farm']);

		$eAction = \farm\ActionLib::getByFarm($eSeries['farm'], fqn: ACTION_RECOLTE);

		return Task::model()
			->select([
				'cultivation',
				'harvestUnit',
				'totalHarvested' => new \Sql('SUM(harvest)', 'float'),
				'totalTime' => new \Sql('SUM(time)', 'float')
			])
			->join(\plant\Quality::model(), 'm2.id = m1.harvestQuality', type: 'LEFT')
			->whereSeries($eSeries)
			->whereAction($eAction)
			->or(
				fn() => $this->where('m2.yield', TRUE),
				fn() => $this->where('m2.yield', NULL)
			)
			->group(['cultivation', 'harvestUnit'])
			->sort([
				'harvestUnit' => SORT_ASC
			])
			->getCollection(index: ['cultivation', 'harvestUnit']);

	}

	public static function getHarvestedBySeriesCollection(\Collection $cSeries, \farm\Farm $eFarm): \Collection {

		$eAction = \farm\ActionLib::getByFarm($eFarm, fqn: ACTION_RECOLTE);

		return Task::model()
			->select([
				'series',
				'cultivation',
				'harvestUnit',
				'totalHarvested' => new \Sql('SUM(harvest)', 'float'),
				'totalTime' => new \Sql('SUM(time)', 'float')
			])
			->join(\plant\Quality::model(), 'm2.id = m1.harvestQuality', type: 'LEFT')
			->whereSeries('IN', $cSeries)
			->whereAction($eAction)
			->or(
				fn() => $this->where('m2.yield', TRUE),
				fn() => $this->where('m2.yield', NULL)
			)
			->group(['series', 'cultivation', 'harvestUnit'])
			->sort([
				'harvestUnit' => SORT_ASC
			])
			->getCollection(index: ['series', 'cultivation', 'harvestUnit']);

	}

	public static function fillHarvestDates(\Collection|Task $cTask): void {

		Task::model()
			->select([
				'harvestWorkingTime' => Timesheet::model()
					->select([
						'date',
						'total' => new \Sql('SUM(time)', 'float'),
					])
					->group($cTask instanceof \Collection ? ['task', 'date'] : 'date')
					->having('total > 0')
					->delegateCollection('task', 'date', callback: function(\Collection $cHarvest) {
						return $cHarvest->makeArray(function($eHarvest, &$key) {
							$key = $eHarvest['date'];
							return round($eHarvest['total'], 1);
						});
					}),
				'harvestDates' => Harvest::model()
					->select([
						'date',
						'total' => new \Sql('SUM(quantity)', 'float'),
					])
					->group($cTask instanceof \Collection ? ['task', 'date'] : 'date')
					->having('total > 0')
					->delegateCollection('task', 'date', callback: function(\Collection $cHarvest) {
						return $cHarvest->makeArray(function($eHarvest, &$key) {
							$key = $eHarvest['date'];
							return round($eHarvest['total'], 1);
						});
					})
			])
			->get($cTask);

	}

	public static function fillHarvestByDate(\Collection $cTask, string $date): void {

		$cTask->expects(['harvestDates']);

		foreach($cTask as $eTask) {
			$eTask['harvestByDate'] = $eTask['harvestDates'][$date] ?? 0.0;
		}

	}

	public static function fillHarvestWeeks(\Collection|Task $cTask): void {

		Task::model()
			->select([
				'harvestWeeks' => Harvest::model()
					->select([
						'week',
						'total' => new \Sql('SUM(quantity)', 'float'),
					])
					->group($cTask instanceof \Collection ? ['task', 'week'] : 'week')
					->having('total > 0')
					->delegateCollection('task', 'week', callback: function(\Collection $cHarvest) {
						return $cHarvest->makeArray(function($eHarvest, &$key) {
							$key = $eHarvest['week'];
							return round($eHarvest['total'], 1);
						});
					})
			])
			->get($cTask);

	}

	public static function fillHarvestByWeek(\Collection $cTask, string $week): void {

		$cTask->expects(['harvestWeeks']);

		foreach($cTask as $eTask) {
			$eTask['harvestByWeek'] = $eTask['harvestWeeks'][$week] ?? 0.0;
		}

	}

	public static function fillDistribution(\Collection $cTask): void {

		Task::model()
			->select([
				'cSlice' => Slice::model()
					->select([
						'variety',
						'partPercent', 'partArea', 'partLength'
					])
					->delegateCollection('cultivation', 'variety', propertyParent: 'cultivation')
			])
			->get($cTask);

		foreach($cTask as $eTask) {

			if(
				$eTask['series']->empty() or
				$eTask['series']['area'] === NULL
			) {
				$eTask['distributionArea'] = NULL;
				continue;
			}

			// Récupération du coefficient d'utilisation de la culture par la variété demandée et stockage dans une propriété distributionArea
			if($eTask['variety']->notEmpty()) {

				// La variété a pu être supprimée de la série entre temps, mais rester présente dans la tâche
				if($eTask['cSlice']->offsetExists($eTask['variety']['id'])) {

					$eSlice = $eTask['cSlice'][$eTask['variety']['id']];

					if($eSlice['partArea'] !== NULL) {
						$eTask['distributionArea'] = $eSlice['partArea'];
					} else if($eSlice['partPercent'] !== NULL) {
						$eTask['distributionArea'] = $eSlice['partPercent'] / 100 * $eTask['series']['area'];
					} else if($eSlice['partLength'] !== NULL) {
						$eTask['distributionArea'] = $eSlice['partLength'] * $eTask['series']['bedWidth'] / 100;
					} else {
						$eTask['distributionArea'] = $eTask['series']['area'];
					}

				} else {
					$eTask['distributionArea'] = 0;
				}

			} else {
				$eTask['distributionArea'] = $eTask['series']['area'];
			}

		}

	}

	public static function buildHarvests(\Collection $cTask, float $harvestAdd, string $harvestDate, string $harvestUnit, string $distribution): void {

		if(\Filter::check('date', $harvestDate) === FALSE) {
			\Fail::log('Task::harvestDates.check');
			return;
		}

		$eTaskReference = $cTask->first();

		$fw = new \FailWatch();

		if($eTaskReference['harvestUnit'] === NULL) {
			$eTaskReference->buildProperty('harvestUnit', $harvestUnit);
		}

		if($fw->ko()) {
			return;
		}

		foreach($cTask as $eTask) {

			$eTask->expects(['harvestDates']);

			if($eTask['action']['fqn'] !== ACTION_RECOLTE) {
				throw new \NotExpectedAction('Bad action');
			}

			// Même unité pour tout le monde
			if($eTask['harvestUnit'] === NULL) {
				$eTask['harvestUnit'] = $eTaskReference['harvestUnit'];
			} else if($eTask['harvestUnit'] !== $eTaskReference['harvestUnit']) {
				\Fail::log('Task::tasks.unit');
				return;
			}

		}

		if(self::checkDistribution($cTask, $distribution) === FALSE) {
			return;
		}

		if(self::distribute(
			$cTask,
			$distribution,
			$harvestAdd,
			1,
			fn(Task $eTask) => $eTask['harvest'] += $eTask['distributed']
		) === FALSE) {
			return;
		}

		// Vérification générale
		foreach($cTask as $eTask) {

			$harvested = $eTask['harvestDates'][$harvestDate] ?? 0.0;
			$newHarvested = $harvested + $eTask['distributed'];

			$eTask['harvest'] = round($eTask['harvest'], 1);

			if($newHarvested < 0) {
				\Fail::log('Task::harvestDates.negative');
				return;
			}

			if($eTask['harvest'] < 0) {
				\Fail::log('Task::harvestMore.negative');
				return;
			}

			$eTask['eHarvest'] = HarvestLib::getElementFromTask($eTask, $harvestDate, $eTask['distributed']);

		}

	}

	public static function checkDistribution(\Collection $cTask, string $distribution): bool {

		if($distribution === 'plant' or $distribution === 'area') {

			foreach($cTask as $eTask) {

				if($eTask['series']->empty()) {
					\Fail::log('Task::tasks.notSeries');
					return FALSE;
				}

				if($eTask['series']['area'] === NULL) {
					\Fail::log('Task::tasks.area');
					return FALSE;
				}

			}

		}

		if($distribution === 'harvest') {

			if($cTask->sum('harvestByDate') === 0.0) {
				\Fail::log('Task::distribution.harvestZero');
				return FALSE;
			}

		}

		return TRUE;

	}

	public static function distribute(\Collection $cTask, string $distribution, float $value, int $precision, \Closure $callback): bool {

		if($cTask->count() === 1) {
			$eTask = $cTask->first();
			$eTask['distributed'] = $value;
			$callback($eTask);
			return TRUE;
		}

		$factor = pow(10, $precision);

		// On travaille avec une précision de 0.1 pour la répartition
		switch($distribution) {

			// Même valeur pour tout le monde
			case 'fair' :

				$totalTasks = $cTask->count();

				$part = floor($value * $factor / $totalTasks);
				$remain = $value * $factor - $part * $totalTasks;

				foreach($cTask as $eTask) {
					$eTask['distributed'] = $part / $factor;
					if($remain-- > 0) {
						$eTask['distributed'] += 1 / $factor;
					}
					$callback($eTask);
				}

				break;

			// Répartition en fonction de la récolte le jour de travail
			case 'harvest' :

				$cTask->expects(['harvestByDate']);

				$totalHarvest = $cTask->sum('harvestByDate');

				$remain = $value * $factor;

				foreach($cTask as $eTask) {

					$part = floor($eTask['harvestByDate'] / $totalHarvest * $value * $factor);

					$eTask['distributed'] = $part / $factor;
					$remain -= $part;

				}

				foreach($cTask as $eTask) {
					if($remain-- > 0) {
						$eTask['distributed'] += 1 / $factor;
					}
					$callback($eTask);
				}

				break;

			// Répartition en fonction de la surface
			case 'area' :

				$totalArea = $cTask->sum('distributionArea');

				$remain = $value * $factor;

				foreach($cTask as $eTask) {

					$part = floor($eTask['distributionArea'] / $totalArea * $value * $factor);

					$eTask['distributed'] = $part / $factor;
					$remain -= $part;

				}

				foreach($cTask as $eTask) {
					if($remain-- > 0) {
						$eTask['distributed'] += 1 / $factor;
					}
					$callback($eTask);
				}

				break;

			case 'plant' :

				$totalPlants = 0;

				foreach($cTask as $eTask) {

					if($eTask['cultivation']->empty() or $eTask['cultivation']['density'] === NULL) {
						\Fail::log('Task::tasks.density');
						return FALSE;
					}

					$totalPlants += $eTask['cultivation']['density'] * $eTask['distributionArea'];

				}

				$remain = $value * $factor;

				foreach($cTask as $eTask) {

					$part = floor($eTask['cultivation']['density'] * $eTask['distributionArea'] / $totalPlants * $value * $factor);

					$eTask['distributed'] = $part / $factor;
					$remain -= $part;

				}

				foreach($cTask as $eTask) {
					if($remain-- > 0) {
						$eTask['distributed'] += 1 / $factor;
					}
					$callback($eTask);
				}

				break;

		}

		return TRUE;

	}

	/**
	 * Construit une liste de Task à partir de Flow
	 */
	public static function buildFromFlow(\Collection $cFlow, Series $eSeries, \Collection $cCultivation, int $season, int $referenceYear = NULL): array {

		$eSeries->expects(['farm']);
		$cCultivation->expects(['crop']);

		$eCategory = \farm\CategoryLib::getByFarm($eSeries['farm'], fqn: CATEGORIE_CULTURE);
		$cTask = (new \Collection())->setDepth(2);
		$cRepeat = new \Collection();

		$referenceYear ??= $season;

		foreach($cFlow as $eFlow) {

			$eAction = $eFlow['action'];

			if($eAction['series'] === FALSE) {
				continue;
			}

			$cRequirement = new \Collection();

			foreach($eFlow['cRequirement'] as $eRequirement) {
				$cRequirement[] = new Requirement([
					'tool' => $eRequirement['tool']
				]);
			}

			$e = ($eFlow['weekOnly'] === NULL) ? new Repeat() : new Task();

			$e->merge([
				'season' => $season,
				'farm' => $eSeries['farm'],
				'series' => $eSeries,
				'plant' => $eFlow['plant'],
				'action' => $eAction,
				'category' => $eCategory,
				'description' => $eFlow['description'],
				'fertilizer' => $eFlow['fertilizer'],
				'crop' => $eFlow['crop'],
				'variety' => new \plant\Variety(),
				'status' => Task::TODO,
				'cRequirement' => $cRequirement
			]);

			if($eFlow['crop']->notEmpty()) {
				$e['cultivation'] = $cCultivation->find(fn($eCultivation) => ($eCultivation['crop']->notEmpty() and $eCultivation['crop']['id'] === $eFlow['crop']['id']), TRUE, 1);
				$e['series'] = $e['cultivation']['series'];
			} else {
				$e['cultivation'] = new Cultivation();
			}

			if($eFlow['weekOnly'] !== NULL) {

				$e['cRequirement'] = $cRequirement;

				$plannedWeek = ($referenceYear + $eFlow['yearOnly']).'-W'.sprintf('%02d', $eFlow['weekOnly']);

				$e['plannedWeek'] = $plannedWeek;
				$e['position'] = $eFlow['positionOnly'];
				$e['display'] = $e['plannedWeek'];

				$cTask->append($e);

			} else {

				$e['tools'] = [];

				foreach($e['cRequirement'] as $eRequirement) {
					$e['tools'][] = $eRequirement['tool']['id'];
				}

				$weekStart = ($referenceYear + $eFlow['yearStart']).'-W'.sprintf('%02d', round($eFlow['weekStart']));
				$weekStop = ($referenceYear + $eFlow['yearStop']).'-W'.sprintf('%02d', round($eFlow['weekStop']));

				$e['frequency'] = $eFlow['frequency'];
				$e['start'] = week_date_day($weekStart, 3);
				$e['stop'] = $weekStop;

				$cRepeat->append($e);

			}

		}

		// Tri en fonction de la semaine et de la position
		$cTask->uasort(function($a, $b) {

			if($a['plannedWeek'] !== $b['plannedWeek']) {
				return strcmp($a['plannedWeek'], $b['plannedWeek']);
			}

			if($b['position'] === NULL) {
				return -1;
			} else if($a['position'] === NULL) {
				return 1;
			} else {
				return ($a['position'] < $b['position']) ? -1 : 1;
			}

		});

		return [$cTask, $cRepeat];

	}

	public static function createCollection(\Collection $c): void {

		foreach($c as $e) {
			self::create($e);
		}

	}

	public static function create(Task $e): void {

		$e->expects([
			'series',
			'farm',
			'category',
			'status',
			'repeatMaster',
			'cTool'
		]);

		if($e['category']['fqn'] === CATEGORIE_CULTURE) {
			$e->expects(['cultivation', 'plant']);
		} else {
			$e['cultivation'] = new Cultivation();
			$e['plant'] = new \plant\Plant();
		}

		if($e['series']->notEmpty()) {
			$e['series']->expects(['season']);
			$e['season'] = $e['series']['season'];
		}

		if($e['status'] === Task::DONE) {

			$e->expects(['doneWeek']);

			$e->add([
				'plannedWeek' => $e['doneWeek'],
				'plannedDate' => $e['doneDate'],
			]);

		}

		Task::model()->beginTransaction();

		if($e['repeatMaster']->notEmpty()) {

			$eRepeat = RepeatLib::createFromTask($e);

			// Si c'est pour une série, on crée immédiatement toutes les tâches car il y a une finitude dans le temps
			if($e['series']->notEmpty()) {
				RepeatLib::createForSeries($eRepeat);
			}

		}

		parent::create($e);

		self::updateTools($e);

		SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);

		Task::model()->commit();

	}

	public static function createFromRepeat(Repeat $e, string $week): void {

		$eTask = new Task($e->extracts(RepeatLib::getTaskProperties()));
		$eTask['repeat'] = $e;

		switch($eTask['status']) {

			case Task::TODO :
				$eTask['plannedWeek'] = $week;
				break;

			case Task::DONE :
				$eTask['plannedWeek'] = $week;
				$eTask['doneWeek'] = $week;
				$eTask['doneDate'] = week_date_day($week, 3);
				break;

		}

		$eTask['cTool'] = new \Collection();

		foreach($e['tools'] as $tool) {
			$eTask['cTool'][] = new \farm\Tool(['id' => $tool]);
		}

		Task::model()->beginTransaction();

			parent::create($eTask);

			self::updateTools($eTask);

			SeriesLib::recalculate($eTask['farm'], $eTask['series'], $eTask['action']);

		Task::model()->commit();

	}

	public static function updateCheck(Task $e, int $position, bool $checked): void {

		if(
			$e['description'] === NULL or
			substr_count($e['description'], "\n") < $position
		) {
			return;
		}

		$lines = explode("\n", $e['description']);

		if(str_starts_with($lines[$position], 'x ') or str_starts_with($lines[$position], 'o ')) {
			$lines[$position] = ($checked ? 'X' : 'O').' '.substr($lines[$position], 2);
		} else if(str_starts_with($lines[$position], 'X ') or str_starts_with($lines[$position], 'O ')) {
			$lines[$position] = ($checked ? 'X' : 'O').' '.substr($lines[$position], 2);
		} else {
			$lines[$position] = ($checked ? 'X' : 'O').' '.$lines[$position];
		}

		$e['description'] = implode("\n", $lines);

		Task::model()
			->select('description')
			->update($e);

	}

	public static function updateTools(Task $e): void {

		$e->expects(['series', 'cultivation', 'farm', 'cTool']);

		Requirement::model()
			->whereTask($e)
			->delete();

		$cRequirement = new \Collection();

		foreach($e['cTool'] as $eTool) {
			$cRequirement[] = new Requirement([
				'tool' => $eTool,
				'series' => $e['series'],
				'cultivation' => $e['cultivation'],
				'task' => $e,
				'farm' => $e['farm'],
			]);
		}

		Requirement::model()->insert($cRequirement);

	}

	public static function updateUser(\Collection $c, \user\User $eUser, string $action): void {

		foreach($c as $e) {

			switch($action) {

				case 'delete' :
					$newUsers = array_diff($e['plannedUsers'], [$eUser['id']]);

					if($newUsers !== $e['plannedUsers']) {
						Task::model()->update($e, [
							'plannedUsers' => $newUsers
						]);
					}

					break;

				case 'add' :
					if(in_array($eUser['id'], $e['plannedUsers']) === FALSE) {

						$newUsers = $e['plannedUsers'];
						$newUsers[] = $eUser['id'];

						Task::model()->update($e, [
							'plannedUsers' => $newUsers
						]);

					}
					break;

			}

		}

	}

	public static function incrementPlannedCollection(\Collection $c, int $increment): void {

		// Traitement en PHP, impossible à faire en une seule requête
		foreach($c as $e) {
			self::incrementPlanned($e, $increment);
		}

	}

	private static function incrementPlanned(Task $e, int $increment): void {

		if($increment < -26 or $increment > 26) {
			return;
		}

		$e->expects(['plannedWeek']);

		$newPlanned = toWeek($e['plannedWeek'].' '.sprintf('%+0d', $increment).' WEEK');

		Series::model()->beginTransaction();

		Task::model()
			->wherePlannedWeek('!=', NULL)
			->update($e, [
				'plannedWeek' => $newPlanned,
				'plannedDate' => NULL
			]);

		SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);

		Series::model()->commit();

	}

	public static function updateTodoCollection(\Collection $c): void {

		Series::model()->beginTransaction();

		// Traitement en PHP car traitements intermédiaires en PHP
		foreach($c as $e) {

			$e['status'] = Task::TODO;
			$e['doneWeek'] = NULL;
			$e['doneDate'] = NULL;

			self::update($e, ['status', 'doneWeek', 'doneDate']);

			SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);

		}

		Series::model()->commit();

	}

	public static function updateDoneCollection(\Collection $c, string $newDone): void {

		Series::model()->beginTransaction();

		// Traitement en PHP car traitements intermédiaires en PHP
		foreach($c as $e) {

			$greatestWeek = 'GREATEST(
				IF(plannedWeek IS NULL, "0000-W00", plannedWeek),
				IF(timesheetStop IS NULL, "0000-W00", CONCAT(SUBSTRING(YEARWEEK(timesheetStop, 1), 1, 4), "-W", SUBSTRING(YEARWEEK(timesheetStop, 1), 5, 2)))
			)';

			$greatestDate = 'GREATEST(
				IF(plannedDate IS NULL, "0000-00-00", plannedDate),
				IF(timesheetStop IS NULL, "0000-00-00", timesheetStop)
			)';

			$e['doneWeek'] = new \Sql('IF('.$greatestWeek.' = "0000-W00", '.Task::model()->format($newDone).', '.$greatestWeek.')');
			$e['doneDate'] = new \Sql('IF('.$greatestDate.' = "0000-00-00", NULL, '.$greatestDate.')');
			$e['status'] = Task::DONE;

			self::update($e, ['doneWeek', 'doneDate', 'status']);

			SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);

		}

		Series::model()->commit();

	}

	public static function updatePlannedCollection(\Collection $c, ?string $newPlanned): void {

		Series::model()->beginTransaction();

		Task::model()
			->whereId('IN', $c)
			->update([
				'plannedWeek' => $newPlanned,
				'plannedDate' => NULL
			]);

		foreach($c as $e) {
			SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);
		}

		Series::model()->commit();

	}

	public static function updatePlannedDateCollection(\Collection $c, ?string $newPlanned): void {

		Series::model()->beginTransaction();

		Task::model()
			->whereId('IN', $c)
			->update([
				'plannedWeek' => toWeek($newPlanned),
				'plannedDate' => $newPlanned
			]);

		foreach($c as $e) {
			SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);
		}

		Series::model()->commit();

	}

	public static function update(Task $e, array $properties = []): void {

		self::prepareUpdate($e, $properties);

		Task::model()->beginTransaction();

		if(array_delete($properties, 'toolsList')) {
			self::updateTools($e);
		}

		if(array_delete($properties, 'planned')) {
			$properties[] = 'plannedWeek';
			$properties[] = 'plannedDate';
		}

		if(array_delete($properties, 'done')) {
			$properties[] = 'doneWeek';
			$properties[] = 'doneDate';
		}

		parent::update($e, $properties);

		if(
			in_array('harvest', $properties) or
			in_array('status', $properties)
		) {
			self::recalculateHarvest($e['farm'], $e['cultivation'], $e['plant']);
		}

		if(in_array('harvest', $properties)) {

			$e->expects(['eHarvest']);

			HarvestLib::create($e['eHarvest']);

		}

		$newTimesheet = [];

		if(in_array('cultivation', $properties)) {

			$e->expects(['action' => ['fqn']]);

			// Mise à jour des quantités récoltées
			if($e['action']['fqn'] === ACTION_RECOLTE) {

				$e->expects(['oldCultivation']);

				self::recalculateHarvest($e['farm'], $e['oldCultivation'], $e['plant']);
				self::recalculateHarvest($e['farm'], $e['cultivation'], $e['plant']);

			}

			$newTimesheet['cultivation'] = $e['cultivation'];

		}

		if(in_array('plant', $properties)) {
			$newTimesheet['plant'] = $e['plant'];
		}

		if($newTimesheet) {

			Timesheet::model()
				->whereTask($e)
				->update($newTimesheet);

		}

		if(array_intersect(['plannedWeek', 'doneWeek'], $properties)) {

			SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);

		}

		Task::model()->commit();

	}

	private static function prepareUpdate(\Element $e, array &$properties) {

		$e['updatedAt'] = new \Sql('NOW()');
		$properties[] = 'updatedAt';

		if(in_array('cultivation', $properties)) {

			$e->expects(['oldCultivation']);

			// La production peut avoir changé
			if($e['cultivation']->notEmpty()) {

				if(
					$e['oldCultivation']->empty() or
					$e['cultivation']['id'] !== $e['oldCultivation']['id']
				) {

					$e['cultivation']->expects(['series']);

					$e['series'] = $e['cultivation']['series'];
					$e['plant'] = $e['cultivation']['plant'];

					$properties[] = 'series';

				}

			} else if($e['cultivation']->empty()) {
				$e['plant'] = new \plant\Plant();
			}

			$properties[] = 'plant';

		}

		if(in_array('status', $properties)) {

			switch($e['status']) {

				case Task::TODO :
					$e['doneWeek'] = NULL;
					$e['doneDate'] = NULL;
					$properties[] = 'doneWeek';
					$properties[] = 'doneDate';
					break;

			}

		}

		$key = array_search('harvestMore', $properties, TRUE);

		if($key !== FALSE) {
			$e['harvest'] = new \Sql('IF(harvest IS NULL, '.Task::model()->format($e['harvestMore']).', harvest + '.Task::model()->format($e['harvestMore']).')');
			$properties[$key] = 'harvest';
		}

	}

	public static function deleteCollection(\Collection $c, bool $recalculate = TRUE): void {

		Task::model()->beginTransaction();

		Timesheet::model()
			->whereTask('IN', $c)
			->delete();

		Harvest::model()
			->whereTask('IN', $c)
			->delete();

		Comment::model()
			->whereTask('IN', $c)
			->delete();

		Requirement::model()
			->whereTask('IN', $c)
			->delete();

		Task::model()->delete($c);

		if($recalculate) {

			foreach($c as $e) {
				self::recalculateHarvest($e['farm'], $e['cultivation'], $e['plant']);
			}

			foreach($c as $e) {
				SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);
			}

		}

		Task::model()->commit();

	}

	public static function deleteRepeat(Task $e): void {

		$e->expects([
			'repeat' => ['current']
		]);

		Task::model()->beginTransaction();

		$week = $e->isDone() ? $e['doneWeek'] : $e['plannedWeek'];

		// Désactivation de la répétition
		$eRepeat = $e['repeat'];
		$eRepeat['stop'] = $week;

		RepeatLib::calculateCompleted($eRepeat);

		Repeat::model()
			->select('stop', 'completed')
			->update($eRepeat);

		// Suppression des tâches
		$cTask = Task::model()
			->select(Task::getSelection())
			->whereRepeat($eRepeat)
			->where(new \Sql('IF(status = "'.Task::DONE.'", doneWeek, plannedWeek)'), '>', $week)
			->getCollection();

		$cTask[] = $e;

		self::deleteCollection($cTask);

		Task::model()->commit();

	}

	public static function delete(Task $e): void {

		$e->expects(['farm', 'series', 'cultivation', 'plant', 'action']);

		Task::model()->beginTransaction();

		Timesheet::model()
			->whereTask($e)
			->delete();

		Harvest::model()
			->whereTask($e)
			->delete();

		Comment::model()
			->whereTask($e)
			->delete();

		Requirement::model()
			->whereTask($e)
			->delete();

		parent::delete($e);

		self::recalculateHarvest($e['farm'], $e['cultivation'], $e['plant']);

		SeriesLib::recalculate($e['farm'], $e['series'], $e['action']);

		Task::model()->commit();

	}

	public static function recalculateTime(Task $eTask): void {

		$eTask->expects(['id', 'farm']);

		$eTimesheet = Timesheet::model()
			->select([
				'sum' => new \Sql('SUM(time)', 'float'),
				'start' => new \Sql('MIN(date)'),
				'stop' => new \Sql('MAX(date)')
			])
			->whereFarm($eTask['farm'])
			->whereTask($eTask)
			->get();

		if($eTimesheet->empty()) {

			$eTask['time'] = NULL;
			$eTask['timesheetStart'] = NULL;
			$eTask['timesheetStop'] = NULL;

		} else {

			$eTask['time'] = $eTimesheet['sum'];
			$eTask['timesheetStart'] = $eTimesheet['start'];
			$eTask['timesheetStop'] = $eTimesheet['stop'];

		}

		$eTask['updatedAt'] = new \Sql('NOW()');

		Task::model()
			->select('time', 'timesheetStart', 'timesheetStop', 'updatedAt')
			->update($eTask);

	}

	public static function recalculateHarvest(\farm\Farm $eFarm, Cultivation $eCultivation, \plant\Plant $ePlant): void {

		if($eCultivation->empty()) {
			return;
		}

		$eCultivation->expects(['mainUnit']);

		$eAction = \farm\ActionLib::getByFarm($eFarm, fqn: ACTION_RECOLTE);

		// Total récolté
		$cQuality = \plant\QualityLib::getForYield($eFarm, $ePlant);

		if($cQuality->notEmpty()) {
			Task::model()->where('harvestQuality IS NULL OR harvestQuality IN ('.implode(', ', $cQuality->getIds()).')');
		} else {
			Task::model()->whereHarvestQuality(NULL);
		}

		$cTask = Task::model()
			->select('harvest', 'harvestUnit', 'harvestQuality', 'doneWeek', 'doneDate')
			->whereAction($eAction)
			->where('harvest < 0 OR harvest > 0')
			->whereCultivation($eCultivation)
			->whereStatus(Task::DONE)
			->getCollection();

		if($cTask->notEmpty()) {

			$harvested = 0;
			$harvestedByUnit = [];
			$months = [];
			$weeks = [];

			foreach($cTask as $eTask) {

				// Pas encore d'unité de récolte pour cette tâche
				if($eTask['harvestUnit'] === NULL) {
					continue;
				}

				// Récolte totale
				if($eTask['harvestUnit'] === $eCultivation['mainUnit']) {
					$harvested += $eTask['harvest'];
				}

				// Récolte par unité
				if(empty($harvestedByUnit[$eTask['harvestUnit']])) {
					$harvestedByUnit[$eTask['harvestUnit']] = 0;
				}
				$harvestedByUnit[$eTask['harvestUnit']] += $eTask['harvest'];

				// Gestion des mois de récolte
				$months[] = date('Y-m', strtotime($eTask['doneWeek'].' + 3 DAYS'));

				$weeks[] = $eTask['doneWeek'];

			}

			$months = array_unique($months);
			sort($months);

			$weeks = array_unique($weeks);
			sort($weeks);

		} else {
			$harvested = NULL;
			$harvestedByUnit = NULL;
			$months = NULL;
			$weeks = NULL;
		}

		$eCultivation['harvested'] = $harvested;
		$eCultivation['harvestedByUnit'] = $harvestedByUnit;
		$eCultivation['harvestMonths'] = $months;
		$eCultivation['harvestWeeks'] = $weeks;

		$eCultivation->calculateHarvestedNormalized();

		Cultivation::model()
			->select('harvested', 'harvestedNormalized', 'harvestedByUnit', 'harvestMonths', 'harvestWeeks')
			->update($eCultivation);

	}

}
?>
