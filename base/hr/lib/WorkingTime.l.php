<?php
namespace hr;

class WorkingTimeLib extends WorkingTimeCrud {

	public static function getPropertiesCreate(): array {
		return ['farm', 'user', 'date', 'time'];
	}

	public static function getByWeek(\farm\Farm $eFarm, \user\User $eUser, string $week): \Collection {

		$firstDate = week_date_starts($week);
		$lastDate = week_date_ends($week);

		return WorkingTime::model()
			->select([
				'date',
				'time'
			])
			->whereFarm($eFarm)
			->whereUser($eUser)
			->where('date BETWEEN '.WorkingTime::model()->format($firstDate).' AND '.WorkingTime::model()->format($lastDate))
			->getCollection(NULL, NULL, 'date');

	}

	public static function fillByWeekFromUsers(\farm\Farm $eFarm, \Collection $cUser, string $week): void {

		$dates = week_dates($week);
		$firstDate = first($dates);
		$lastDate = last($dates);

		$cWorkingTime = WorkingTime::model()
			->select(['user', 'date', 'time'])
			->whereFarm($eFarm)
			->whereUser('IN', $cUser)
			->whereDate('BETWEEN', new \Sql(WorkingTime::model()->format($firstDate).' AND '.WorkingTime::model()->format($lastDate)))
			->getCollection(NULL, NULL, ['user', 'date']);

		foreach($cUser as $eUser) {

			$eUser['workingTime'] = [];

			foreach($dates as $date) {
				$eUser['workingTime'][$date] = $cWorkingTime[$eUser['id']][$date]['time'] ?? NULL;
			}

		}

	}

	public static function create(WorkingTime $e): void {

		$e->expects(['time', 'farm', 'user', 'date']);

		if($e['time'] > 0.0) {

			try {
				WorkingTime::model()->insert($e);

			} catch(\DuplicateException) {

				unset($e['id']);

				WorkingTime::model()
					->select(array_diff(self::getPropertiesCreate(), ['farm', 'user', 'date']))
					->whereFarm($e['farm'])
					->whereUser($e['user'])
					->whereDate($e['date'])
					->update($e);

			}

		} else {

			WorkingTime::model()
				->whereFarm($e['farm'])
				->whereUser($e['user'])
				->whereDate($e['date'])
				->delete();

		}

	}

	public static function calculateMissing(): void {

		$c = \series\Timesheet::model()
			->select([
				'farm', 'user', 'date',
				'time' => new \Sql('SUM(m1.time)')
			])
			->join(WorkingTime::model(), 'm1.user=m2.user and m1.farm=m2.farm and m1.date=m2.date', 'LEFT')
			->where('m2.time', NULL)
			->group(new \Sql('m1.user, m1.farm, m1.date'))
			->having(new \Sql('SUM(m1.time) > 0'))
			->getCollection();

		foreach($c as $e) {

			$eWorkingTime = new WorkingTime([
				'farm' => $e['farm'],
				'user' => $e['user'],
				'date' => $e['date'],
				'time' => $e['time'],
				'auto' => TRUE
			]);

			WorkingTimeLib::create($eWorkingTime);

		}

	}

}
