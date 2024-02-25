<?php
namespace series;

class TimesheetLib extends TimesheetCrud {

	public static function getPropertiesCreate(): array {
		return ['date', 'user', 'time'];
	}

	public static function getPropertiesUpdate(): array {
		return ['time'];
	}

	public static function getByUserAndTasks(\user\User $eUser, \Collection $cTask): \Collection {

		return Timesheet::model()
			->select([
				'id',
				'time',
				'date'
			])
			->whereTask('IN', $cTask)
			->whereUser($eUser)
			->sort(['date' => SORT_ASC])
			->getCollection(index: ['date', NULL]);

	}

	public static function fillTimesByDate(\farm\Farm $eFarm, \Collection $cUser, string $week): void {

		$dates = week_dates($week);
		$firstDate = first($dates);
		$lastDate = last($dates);

		$cTimesheet = Timesheet::model()
			->select([
				'user', 'date',
				'totalTime' => new \Sql('SUM(time)', 'float')
			])
			->whereFarm($eFarm)
			->whereUser('IN', $cUser)
			->whereDate('BETWEEN', new \Sql(Timesheet::model()->format($firstDate).' AND '.Timesheet::model()->format($lastDate)))
			->group(['user', 'date'])
			->getCollection(NULL, NULL, ['user', 'date']);

		foreach($cUser as $eUser) {

			$eUser['timesheetTime'] = [];

			foreach($dates as $date) {
				$eUser['timesheetTime'][$date] = $cTimesheet[$eUser['id']][$date]['totalTime'] ?? NULL;
			}

		}

	}

	public static function fillTimesByTask(\Collection $cUser, Task $eTask): void {
		self::fillTimesByTasks($cUser, new \Collection([$eTask]));
	}

	public static function fillTimesByTasks(\Collection $cUser, \Collection $cTask): void {

		$cTimesheet = Timesheet::model()
			->select([
				'user',
				'totalTime' => new \Sql('SUM(time)', 'float')
			])
			->whereTask('IN', $cTask)
			->sort(['totalTime' => SORT_DESC])
			->group('user')
			->getCollection(NULL, NULL, 'user');

		foreach($cUser as $eUser) {

			$eUser['time'] = $cTimesheet[$eUser['id']]['totalTime'] ?? NULL;

		}

	}

	public static function getTimesheetsByDaily(\farm\Farm $eFarm, string $week, \user\User $eUser = new \user\User()): \Collection {

		if($eUser->notEmpty()) {
			Timesheet::model()->whereUser($eUser);
		}

		$firstDate = week_date_starts($week);
		$lastDate = week_date_ends($week);

		return Timesheet::model()
			->select('task', 'date')
			->whereFarm($eFarm)
			->whereDate('BETWEEN', new \Sql(Timesheet::model()->format($firstDate).' AND '.Timesheet::model()->format($lastDate)))
			->getCollection(index: ['date', NULL]);

	}

	public static function getTasksByWeek(\farm\Farm $eFarm, string $week, \user\User $eUser = NULL): \Collection {

		if($eUser !== NULL) {
			Timesheet::model()->whereUser($eUser);
		}

		$firstDate = week_date_starts($week);
		$lastDate = week_date_ends($week);

		return Timesheet::model()
			->whereFarm($eFarm)
			->whereDate('BETWEEN', new \Sql(Timesheet::model()->format($firstDate).' AND '.Timesheet::model()->format($lastDate)))
			->group('task')
			->getColumn('task');

	}

	public static function getTimesByWeek(\farm\Farm $eFarm, \user\User $eUser, string $week): array {

		$firstDate = week_date_starts($week);
		$lastDate = week_date_ends($week);

		return Timesheet::model()
			->select([
				'date',
				'totalTime' => new \Sql('SUM(time)', 'float')
			])
			->whereFarm($eFarm)
			->whereUser($eUser)
			->where('date BETWEEN '.Timesheet::model()->format($firstDate).' AND '.Timesheet::model()->format($lastDate))
			->group('date')
			->getCollection()
			->makeArray(function(Timesheet $eTimesheet, &$key) {
				$key = $eTimesheet['date'];
				return round($eTimesheet['totalTime'], 2);
			});

	}

	public static function delegateByTask(\user\User $eUser = new \user\User(), ?string $timesheetWeek = NULL, ?string $timesheetDate = NULL) {

		if($timesheetWeek !== NULL) {

			$firstDate = date('Y-m-d', strtotime($timesheetWeek));
			$lastDate = date('Y-m-d', strtotime($timesheetWeek.' + 6 DAY'));

			Timesheet::model()->whereDate('BETWEEN', new \Sql(Timesheet::model()->format($firstDate).' AND '.Timesheet::model()->format($lastDate)));

		} else if($timesheetDate !== NULL) {
			Timesheet::model()->whereDate($timesheetDate);
		}

		if($eUser->notEmpty()) {
			Timesheet::model()->whereUser($eUser);
		}

		return Timesheet::model()
			->select([
				'user',
				'totalTime' => new \Sql('SUM(time)', 'float')
			])
			->group(['task', 'user'])
			->sort(['user' =>  SORT_ASC])
			->delegateCollection('task', 'user', function($c) {
				return $c->makeArray(function(Timesheet $e, &$key) {

					$key = $e['user']['id'];
					return $e['totalTime'];

				});
			});

	}

	public static function create(Timesheet $e): void {

		$e->expects(['time']);

		try {

			parent::create($e);

			if($e['time'] > 0) {
				TaskLib::recalculateTime($e['task']);
			}

		} catch(\DuplicateException $e) {

			Timesheet::fail('duplicate');

		}


	}


	public static function writeByTasks(\Collection $cTask, Timesheet $eTimesheetBase, float $timeAdd, string $distribution): void {

		if($distribution === 'harvest') {
			\series\TaskLib::fillHarvestDates($cTask);
			\series\TaskLib::fillHarvestByDate($cTask, $eTimesheetBase['date']);
		}

		\series\TaskLib::fillDistribution($cTask);

		// On récupére les parts de chaque variété
		if(TaskLib::checkDistribution($cTask, $distribution) === FALSE) {
			return;
		}

		$cTimesheet = new \Collection();

		$precision = 6;

		if(TaskLib::distribute(
			$cTask,
			$distribution,
			$timeAdd,
			$precision,
			function(Task $eTask) use ($cTimesheet, $eTimesheetBase, $precision) {

				$eTimesheet = (clone $eTimesheetBase)->merge([
					'task' => $eTask
				]);

				$cTimesheet[] = $eTimesheet->merge([
					'time' => $eTask['distributed']
				]);

			}
		) === FALSE) {
			return;
		}

		Timesheet::model()->beginTransaction();

		foreach($cTimesheet as $eTimesheetNew) {

			$eTimesheetNew['time'] = round($eTimesheetNew['time'], $precision);

			$eTimesheetNew->merge([
				'series' => $eTimesheetNew['task']['series'],
				'cultivation' => $eTimesheetNew['task']['cultivation'],
				'plant' => $eTimesheetNew['task']['plant'],
				'week' => toWeek($eTimesheetNew['date'])
			]);

			$eTimesheetCurrent = Timesheet::model()
				->select('id', 'time')
				->whereTask($eTimesheetNew['task'])
				->whereUser($eTimesheetNew['user'])
				->whereDate($eTimesheetNew['date'])
				->get();

			if($eTimesheetCurrent->notEmpty()) {

				if($eTimesheetNew['time'] === 0.0) {
					continue;
				}

				$eTimesheetNew['id'] = $eTimesheetCurrent['id'];
				$eTimesheetNew['time'] += $eTimesheetCurrent['time'];

				// Workaround sur les arrondis
				if($eTimesheetNew['time'] < 0.0001 and $eTimesheetNew['time'] > -0.0001) {
					$eTimesheetNew['time'] = 0;
				}

				if($eTimesheetNew['time'] >= 0) {

					Timesheet::model()
						->select('time')
						->update($eTimesheetNew);

				} else {

					Timesheet::model()->rollBack();
					Timesheet::fail('time.negative');

					return;

				}

			} else {

				if($eTimesheetNew['time'] < 0) {

					Timesheet::model()->rollBack();
					Timesheet::fail('time.negative');

					return;

				}

				Timesheet::model()->insert($eTimesheetNew);

			}

			TaskLib::recalculateTime($eTimesheetNew['task']);

		}

		Timesheet::model()->commit();

	}

	public static function update(Timesheet $e, array $properties): void {

		$e->expects(['task', 'farm']);

		Timesheet::model()->beginTransaction();

		parent::update($e, $properties);

		if(in_array('time', $properties)) {

			$e['task']['farm'] = $e['farm'];

			TaskLib::recalculateTime($e['task']);

		}

		Timesheet::model()->commit();

	}

	public static function delete(Timesheet $e): void {

		$e->expects(['task', 'farm']);

		Timesheet::model()->beginTransaction();

		parent::delete($e);

		$e['task']['farm'] = $e['farm'];

		TaskLib::recalculateTime($e['task']);

		Timesheet::model()->commit();

	}

	public static function deleteCollection(\Collection $c): void {

		$c->expects(['task', 'farm']);

		// Suppression un à un car beaucoup traitements personnalisés à chaque fois
		foreach($c as $e) {
			self::delete($e);
		}

	}

	public static function deleteByUser(\user\User $eUser, \Collection $cTask): void {

		Timesheet::model()->beginTransaction();

		Timesheet::model()
			->whereUser($eUser)
			->whereTask('IN', $cTask)
			->delete();

		foreach($cTask as $eTask) {
			TaskLib::recalculateTime($eTask);
		}

		Timesheet::model()->commit();


	}

}
?>
