<?php
namespace series;

class AnalyzeLib {

	public static function getFarmMonths(\farm\Farm $eFarm, int $year): \Collection {

		$cWorkingTimeMonth = \hr\WorkingTime::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM date)', 'int'),
				'time' => new \Sql('SUM(time)', 'float')
			])
			->whereFarm($eFarm)
			->where(new \Sql('EXTRACT(YEAR FROM date)'), $year)
			->group(new \Sql('month'))
			->sort(new \Sql('month DESC'))
			->getCollection(NULL, NULL, ['month']);

		return $cWorkingTimeMonth;

	}

	public static function getGlobalWorkingTime(\farm\Farm $eFarm, int $year, ?int $month, ?string $week): ?float {

		$workingTime = \hr\WorkingTime::model()
			->whereFarm($eFarm)
			->where('EXTRACT(YEAR from date) = '.$year)
			->where($month ? 'EXTRACT(MONTH FROM date) = '.$month : NULL)
			->where($week ? 'WEEK(date, 1) = '.week_number($week) : NULL)
			->getValue(new \Sql('SUM(time)', 'float'));

		if($workingTime !== NULL) {
			return round($workingTime, 2);
		} else {
			return NULL;
		}

	}

	public static function getMonthlyWorkingTime(\farm\Farm $eFarm, int $year): \Collection {

		$eUserOnline = \user\ConnectionLib::getOnline();

		return \hr\WorkingTime::model()
			->select([
				'user' => ['firstName', 'lastName', 'vignette'],
				'month' => new \Sql('SUBSTRING(date, 1, 7)'),
				'time' => new \Sql('SUM(time)', 'float')
			])
			->whereFarm($eFarm)
			->where('EXTRACT(YEAR from date) = '.\hr\WorkingTime::model()->format($year))
			->group(['user', 'month'])
			->sort(new \Sql('IF(user = '.$eUserOnline['id'].', 0, user) ASC'))
			->getCollection(NULL, NULL, ['user', 'month']);

	}

	public static function getWeeklyWorkingTime(\farm\Farm $eFarm, int $year): array {

		$start = (new \DateTime())->setISODate($year, 1)->format('Y-m-d');
		$stop = (new \DateTime())->setISODate($year, 52, 7)->format('Y-m-d');
		$currentWeek = min(52, (int)date('W'));

		$ccPresence =\hr\PresenceLib::getBetween($eFarm, $start, $stop);

		$distribution = [];

		foreach($ccPresence as $user => $cPresence) {

			$dates = [];
			$weekStart = 1;
			$weekStop = $currentWeek;

			foreach($cPresence as $ePresence) {

				if($ePresence['to'] === NULL) {

					$dates[] = fn() => $this->whereDate('>=', $ePresence['from']);

					if(date_year($ePresence['from']) === $year) {
						$weekStart = week_number(toWeek($ePresence['from']));
					}

				} else {

					if(date_year($ePresence['from']) === $year) {
						$weekStart = week_number(toWeek($ePresence['from']));
					}

					if(date_year($ePresence['to']) === $year) {
						$weekStop = min($weekStop, week_number(toWeek($ePresence['to'])));
					}

				}

			}

			$distribution[$user] = \hr\WorkingTime::model()
				->select([
					'user',
					'week' => new \Sql('EXTRACT(week FROM date)', 'int'),
					'time' => new \Sql('IF(SUM(time) < 11, 10, IF(SUM(time) < 40, 40, IF(SUM(time) < 50, 50, 51)))', 'int')
				])
				->whereFarm($eFarm)
				->whereUser($user)
				->or(...$dates)
				->where('EXTRACT(YEAR from date) = '.\hr\WorkingTime::model()->format($year))
				->group(['week'])
				->getCollection()
				->reduce(function($eWorkingTime, $list) {
					$list[$eWorkingTime['time']] = ($list[$eWorkingTime['time']] ?? 0) + 1;
					return $list;
				}, []);

			$distribution[$user][0] = max(0, ($weekStop - $weekStart) - array_sum($distribution[$user]));

		}

		return $distribution;

	}

	public static function getActionTimesheetByUser(\farm\Farm $eFarm, int $year): \Collection {

		return Timesheet::model()
			->select([
				'user',
				'time' => new \Sql('SUM(m1.time)', 'float'),
			])
			->join(Task::model()
				->select([
					'action' => ['name', 'color'],
					'category' => ['name'],
				]), 'm2.id = m1.task')
			->where('m1.farm', $eFarm)
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->group(['user', 'action', 'category'])
			->sort(new \Sql('m1_time DESC'))
			->getCollection(index: ['user', NULL]);

	}

	public static function getCategoryTimesheet(\farm\Farm $eFarm, int $year, ?int $month, ?string $week): \Collection {

		return Timesheet::model()
			->select([
				'time' => new \Sql('SUM(m1.time)', 'float'),
			])
			->join(Task::model()
				->select([
					'category' => ['name'],
				]), 'm2.id = m1.task')
			->where('m1.farm', $eFarm)
			->where('m1.time > 0')
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->where($month ? 'EXTRACT(MONTH FROM date) = '.$month : NULL)
			->where($week ? 'WEEK(date, 1) = '.week_number($week) : NULL)
			->group(['category'])
			->sort(new \Sql('m1_time DESC'))
			->getCollection();

	}

	public static function getCategoryMonthly(\farm\Farm $eFarm, int $year): \Collection {

		return Timesheet::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM date)', 'int'),
				'time' => new \Sql('SUM(m1.time)', 'float'),
			])
			->join(Task::model()
				->select([
					'category' => ['name'],
				]), 'm2.id = m1.task')
			->where('m1.farm', $eFarm)
			->where('m1.time > 0')
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->group([new \Sql('m1_month'), 'category'])
			->getCollection(index: ['category', 'month']);

	}

	public static function getPlantMonths(\plant\Plant $ePlant, int $year): \Collection {

		// On récupère les séries potentiellement concernées par l'année
		$cCultivation = Cultivation::model()
			->select(['id', 'series'])
			->wherePlant($ePlant)
			->whereSeason('IN', [$year - 1, $year, $year + 1])
			->getCollection();

		if($cCultivation->empty()) {
			return new \Collection();
		}

		$cSeries = $cCultivation->getColumnCollection('series');

		return Timesheet::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM date)', 'int'),
				'time' => new \Sql('SUM(IF(m1.series IS NOT NULL AND m1.cultivation IS NULL, m1.time / m2.plants, m1.time))')
			])
			->join(Series::model(), 'm2.id = m1.series', 'LEFT')
			->or(
				fn() => $this->where('m1.series', 'IN', $cSeries),
				fn() => $this->where('m1.plant', $ePlant),
			)
			->or(
				fn() => $this->where('m1.cultivation', NULL),
				fn() => $this->where('m1.cultivation', 'IN', $cCultivation),
			)
			->where(new \Sql('EXTRACT(YEAR FROM date)'), $year)
			->group(new \Sql('m1_month'))
			->getCollection(NULL, NULL, 'month');

	}

	public static function getPlantsTimesheet(\farm\Farm $eFarm, int $year, ?int $month, ?string $week): \Collection {

		return Timesheet::model()
			->select([
				'plant' => ['vignette', 'fqn', 'name'],
				'time' => function($e) {
					return round($e['timePlant'] + $e['timeShared'], 2);
				},
				'timePlant' => new \Sql('SUM(time)', 'float'),
				'timeNoSeries' => new \Sql('SUM(IF(series IS NULL, time, 0))', 'float'),
				'timeShared' => (new TimesheetModel())
					->select([
						'propertySource' => new \Sql('m3.plant')
					])
					->join(Task::model(), 'm2.id = m1.task')
					->join(Cultivation::model(), 'm3.series = m2.series')
					->join(Series::model(), 'm4.id = m2.series')
					->where('m1.farm', $eFarm)
					->where('m2.cultivation IS NULL')
					->where('EXTRACT(YEAR FROM date) = '.$year)
					->where($month ? 'EXTRACT(MONTH FROM date) = '.$month : NULL)
					->where($week ? 'WEEK(date, 1) = '.week_number($week) : NULL)
					->group(new \Sql('m3.plant'))
					->delegateProperty('propertySource', new \Sql('SUM(m1.time / m4.plants)', 'float'), fn($value) => $value ?? 0, 'plant')
			])
			->where('farm', $eFarm)
			->where('time > 0')
			->where('plant IS NOT NULL')
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->where($month ? 'EXTRACT(MONTH FROM date) = '.$month : NULL)
			->where($week ? 'WEEK(date, 1) = '.week_number($week) : NULL)
			->group(['plant'])
			->sort(new \Sql('timePlant DESC'))
			->getCollection();

	}

	public static function getMonthlyPlantsTimesheet(\farm\Farm $eFarm, int $year): \Collection {

		return Timesheet::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM date)', 'int'),
				'plant' => ['vignette', 'fqn', 'name'],
				'propertyDelegate' => new \Sql('CONCAT(plant, EXTRACT(MONTH FROM date))', 'int'),
				'time' => function($e) {
					return round($e['timePlant'] + $e['timeShared'] + $e['timeShared'], 2);
				},
				'timePlant' => new \Sql('SUM(time)', 'float'),
				'timeNoSeries' => new \Sql('SUM(IF(series IS NULL, time, 0))', 'float'),
				'timeShared' => (new TimesheetModel())
					->select([
						'propertySource' => new \Sql('CONCAT(m3.plant, "-", EXTRACT(MONTH FROM date))')
					])
					->join(Task::model(), 'm2.id = m1.task')
					->join(Cultivation::model(), 'm3.series = m2.series')
					->join(Series::model(), 'm4.id = m2.series')
					->where('m1.farm', $eFarm)
					->where('m2.cultivation IS NULL')
					->where('EXTRACT(YEAR FROM date) = '.$year)
					->group([new \Sql('CONCAT(m3.plant, "-", EXTRACT(MONTH FROM date))')])
					->delegateProperty('propertySource', new \Sql('SUM(m1.time / m4.plants)', 'float'), fn($value) => $value ?? 0, 'propertyDelegate')
			])
			->where('farm', $eFarm)
			->where('plant IS NOT NULL')
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->group([new \Sql('CONCAT(plant, EXTRACT(MONTH FROM date)), EXTRACT(MONTH FROM date), plant')])
			->getCollection(index: ['plant', 'month']);

	}

	public static function getPlantTimesheet(\plant\Plant $ePlant, int $year): \Collection {

		// On récupère les séries potentiellement concernées par l'année
		$cCultivation = Cultivation::model()
			->select(['id', 'series'])
			->wherePlant($ePlant)
			->whereSeason('BETWEEN', new \Sql(($year - 3).' AND '.($year + 3)))
			->getCollection();

		if($cCultivation->empty()) {
			return new \Collection();
		}

		$cSeries = $cCultivation->getColumnCollection('series');

		return Timesheet::model()
			->select([
				'year' => new \Sql('EXTRACT(YEAR FROM date)', 'int'),
				'time' => new \Sql('SUM(IF(m1.series IS NOT NULL AND m1.cultivation IS NULL, m1.time / m2.plants, m1.time))')
			])
			->join(Series::model(), 'm2.id = m1.series', 'LEFT')
			->or(
				fn() => $this->where('m1.series', 'IN', $cSeries),
				fn() => $this->where('m1.plant', $ePlant),
			)
			->or(
				fn() => $this->where('m1.cultivation', NULL),
				fn() => $this->where('m1.cultivation', 'IN', $cCultivation),
			)
			->where(new \Sql('EXTRACT(YEAR FROM date)'), 'BETWEEN', new \Sql(($year - 2).' AND '.($year + 2)))
			->group(new \Sql('m1_year'))
			->sort(new \Sql('m1_year DESC'))
			->getCollection();

	}

	public static function getActionTimesheetByPlant(\plant\Plant $ePlant, int $year): array {

		// On récupère les séries potentiellement concernées par l'année
		$cCultivation = Cultivation::model()
			->select(['id', 'series'])
			->wherePlant($ePlant)
			->whereSeason('IN', [$year - 1, $year, $year + 1])
			->getCollection();

		if($cCultivation->empty()) {
			return [new \Collection(), new \Collection()];
		}

		$cSeries = $cCultivation->getColumnCollection('series');

		$ccTimesheet = Timesheet::model()
			->select([
				'time' => new \Sql('SUM(IF(m1.series IS NOT NULL AND m1.cultivation IS NULL, m1.time / m2.plants, m1.time))'),
				'user' => ['firstName', 'lastName', 'vignette']
			])
			->join(Series::model(), 'm2.id = m1.series', 'LEFT')
			->join(Task::model()
				->select([
					'action' => ['name', 'color'],
				]), 'm3.id = m1.task')
			->or(
				fn() => $this->where('m1.series', 'IN', $cSeries),
				fn() => $this->where('m1.plant', $ePlant),
			)
			->or(
				fn() => $this->where('m1.cultivation', NULL),
				fn() => $this->where('m1.cultivation', 'IN', $cCultivation),
			)
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->group(['m3_action', 'm1_user'])
			->sort(new \Sql('m1_time DESC'))
			->getCollection(index: ['action', 'user']);

		$cTimesheetAction = new \Collection();
		$cTimesheetUser = new \Collection();

		foreach($ccTimesheet as $action => $cTimesheet) {

			$cTimesheetAction[$action] = new Timesheet([
				'action' => $cTimesheet->first()['action'],
				'time' => $cTimesheet->sum('time'),
				'cTimesheetUser' => $cTimesheet
			]);

			foreach($cTimesheet as $user => $eTimesheet) {

				if($cTimesheetUser->offsetExists($user) === FALSE) {
					$cTimesheetUser[$user] = new Timesheet([
						'user' => $eTimesheet['user'],
						'time' => 0,
					]);
				}

				$cTimesheetUser[$user]['time'] += $eTimesheet['time'];

			}

		}

		$cTimesheetUser->sort(['time' => SORT_DESC]);

		return [$cTimesheetAction, $cTimesheetUser];

	}

	public static function getSeriesTimesheet(\farm\Farm $eFarm, int $year, ?int $month, ?string $week): \Collection {

		return Timesheet::model()
			->select([
				'series' => ['name', 'mode'],
				'time' => new \Sql('SUM(time)', 'float'),
			])
			->whereFarm($eFarm)
			->where('time > 0')
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->where($month ? 'EXTRACT(MONTH FROM date) = '.$month : NULL)
			->where($week ? 'WEEK(date, 1) = '.week_number($week) : NULL)
			->group(['series'])
			->sort(new \Sql('time DESC'))
			->getCollection(index: 'series');

	}

	public static function getSeriesMonthly(\farm\Farm $eFarm, int $year): \Collection {

		return Timesheet::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM date)', 'int'),
				'hasSeries' => new \Sql('series IS NOT NULL', 'bool'),
				'time' => new \Sql('SUM(time)', 'float'),
			])
			->whereFarm($eFarm)
			->where('time > 0')
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->group([new \Sql('month'), 'hasSeries'])
			->sort('hasSeries')
			->getCollection(index: ['hasSeries', 'month']);

	}

	public static function getActionTimesheet(\farm\Farm $eFarm, int $year, ?int $month, ?string $week): \Collection {

		return Timesheet::model()
			->select([
				'time' => new \Sql('SUM(m1.time)', 'float'),
			])
			->join(Task::model()
				->select([
					'action' => ['name', 'color'],
					'category' => ['name'],
				]), 'm2.id = m1.task')
			->where('m1.farm', $eFarm)
			->where('m1.time > 0')
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->where($month ? 'EXTRACT(MONTH FROM date) = '.$month : NULL)
			->where($week ? 'WEEK(date, 1) = '.week_number($week) : NULL)
			->group(['action', 'category'])
			->sort(new \Sql('m1_time DESC'))
			->getCollection();


	}

	public static function getActionMonthly(\farm\Farm $eFarm, int $year): \Collection {

		return Timesheet::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM date)', 'int'),
				'time' => new \Sql('SUM(m1.time)', 'float'),
			])
			->join(Task::model()
				->select([
					'action',
					'category',
				]), 'm2.id = m1.task')
			->where('m1.time > 0')
			->where('m1.farm', $eFarm)
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->group([new \Sql('m1_month'), 'action', 'category'])
			->sort(new \Sql('m1_time DESC'))
			->getCollection(index: ['action', 'category', 'month']);


	}

	public static function getYears(\farm\Farm $eFarm): array {

		return \Cache::redis()->query(
			'farm-series-years-'.$eFarm['id'],
			function() use ($eFarm) {

				$firstYear = Timesheet::model()
					->whereFarm($eFarm)
					->getValue(new \Sql('MIN(EXTRACT(YEAR FROM date))', 'int')) ?? (int)date('Y');

				$lastYear = (int)date('Y');

				$years = [];
				for($year = $lastYear; $year >= $firstYear; $year--) {
					$years[] = $year;
				}

				return $years;

			},
			date('W') <= 51 ? 86400 * 7 : 86400
		);

	}

	public static function getExportTasks(\farm\Farm $eFarm, int $year): array {

		$ccTimesheet = Timesheet::model()
			->select([
				'user' => ['firstName', 'lastName', 'visibility'],
				'time',
				'date'
			])
			->join(Task::model()
				->select([
					'task' => new \Sql('m2.id'),
					'action' => ['name'],
					'category' => ['name'],
					'series' => ['name', 'mode'],
					'plant' => ['name'],
					'variety' => ['name'],
					'description',
					'harvestUser' => new \Sql('IF(harvest IS NOT NULL, IF(m2.time > 0, FLOOR(harvest * m1.time / m2.time * 100) / 100, 0), NULL)', 'float'),
					'harvest',
					'harvestUnit',
					'harvestQuality' => ['name']
				]), 'm2.id = m1.task')
			->where('m1.farm', $eFarm)
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->getCollection(index: ['task', NULL]);

		$output = [];

		foreach($ccTimesheet as $cTimesheet) {

			$eTimesheetFirst = $cTimesheet->first();

			if($eTimesheetFirst['harvest'] !== NULL) {

				$harvestUser = $cTimesheet->sum('harvestUser');
				$harvestTotal = $eTimesheetFirst['harvest'];

				if($harvestUser < $harvestTotal) {
					$eTimesheetFirst['harvestUser'] += round($harvestTotal - $harvestUser, 2);
				}

			}

			foreach($cTimesheet as $eTimesheet) {

				$output[] = [
					\util\DateUi::numeric($eTimesheet['date']),
					($eTimesheet['user']['firstName'] === NULL) ? $eTimesheet['user']['lastName'] : $eTimesheet['user']['firstName'].' '.$eTimesheet['user']['lastName'],
					$eTimesheet['category']['name'],
					$eTimesheet['action']['name'],
					\util\TextUi::csvNumber($eTimesheet['time']),
					$eTimesheet['series']->empty() ? '' : $eTimesheet['series']['id'],
					$eTimesheet['series']->empty() ? '' : $eTimesheet['series']['name'],
					$eTimesheet['plant']->empty() ? '' : $eTimesheet['plant']['name'],
					$eTimesheet['variety']->empty() ? '' : $eTimesheet['variety']['name'],
					$eTimesheet['harvestUser'] ? \util\TextUi::csvNumber($eTimesheet['harvestUser']) : '',
					($eTimesheet['harvestUser'] and $eTimesheet['harvestUnit']) ? \main\UnitUi::getSingular($eTimesheet['harvestUnit']) : '',
					$eTimesheet['harvestQuality']->empty() ? '' : $eTimesheet['harvestQuality']['name'],
					$eTimesheet['description'] ?? ''
				];

			}

		}

		return $output;

	}

	public static function getExportHarvests(\farm\Farm $eFarm, int $year): array {

		$cHarvest = Harvest::model()
			->select([
				'date',
				'task' => [
					'series' => ['name'],
					'plant' => ['name'],
					'variety' => ['name'],
					'harvestQuality' => ['name'],
				],
				'quantity' => new \Sql('SUM(quantity)', 'float'),
				'unit'
			])
			->whereFarm($eFarm)
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->where('quantity IS NOT NULL')
			->group(['date', 'unit', 'task'])
			->sort('date')
			->getCollection();

		$output = [];

		foreach($cHarvest as $eHarvest) {

			$eTask = $eHarvest['task'];

			if(round($eHarvest['quantity'], 2) === 0.0) {
				continue;
			}

			$output[] = [
				\util\DateUi::numeric($eHarvest['date']),
				$eTask['series']->empty() ? '' : $eTask['series']['id'],
				$eTask['series']->empty() ? '' : $eTask['series']['name'],
				$eTask['plant']->empty() ? '' : $eTask['plant']['name'],
				$eTask['variety']->empty() ? '' : $eTask['variety']['name'],
				\util\TextUi::csvNumber($eHarvest['quantity']),
				\main\UnitUi::getSingular($eHarvest['unit']),
				$eTask['harvestQuality']->empty() ? '' : $eTask['harvestQuality']['name']
			];

		}

		return $output;

	}

}
?>
