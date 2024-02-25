<?php
namespace farm;

class AnalyzeLib {

	public static function getActionMonths(\farm\Action $eAction, Category $eCategory, int $year): array {

		$ccTimesheet = \series\Timesheet::model()
			->select([
				'month' => new \Sql('EXTRACT(MONTH FROM date)', 'int'),
				'time' => new \Sql('SUM(m1.time)'),
				'user' => ['firstName', 'lastName', 'vignette']
			])
			->join(\series\Task::model(), 'm2.id = m1.task')
			->where('m2.action', $eAction)
			->where('m2.category', $eCategory)
			->where(new \Sql('EXTRACT(YEAR FROM date)'), $year)
			->group(new \Sql('m1_month, m1_user'))
			->sort(new \Sql('m1_time DESC'))
			->getCollection(NULL, NULL, ['month', 'user']);

		$cTimesheetMonth = new \Collection();
		$cTimesheetUser = new \Collection();

		foreach($ccTimesheet as $month => $cTimesheet) {

			$cTimesheetMonth[$month] = new \series\Timesheet([
				'month' => $month,
				'time' => $cTimesheet->sum('time'),
				'cTimesheetUser' => $cTimesheet
			]);

			foreach($cTimesheet as $user => $eTimesheet) {

				if($cTimesheetUser->offsetExists($user) === FALSE) {
					$cTimesheetUser[$user] = new \series\Timesheet([
						'user' => $eTimesheet['user'],
						'time' => 0,
					]);
				}

				$cTimesheetUser[$user]['time'] += $eTimesheet['time'];

			}

		}

		$cTimesheetUser->sort(['time' => SORT_DESC]);

		return [$cTimesheetMonth, $cTimesheetUser];

	}

	public static function getActionTimesheet(\farm\Action $eAction, Category $eCategory, int $year): \Collection {

		return \series\Timesheet::model()
			->select([
				'year' => new \Sql('EXTRACT(YEAR FROM date)', 'int'),
				'time' => new \Sql('SUM(m1.time)')
			])
			->join(\series\Task::model(), 'm2.id = m1.task')
			->where('m2.action', $eAction)
			->where('m2.category', $eCategory)
			->where(new \Sql('EXTRACT(YEAR FROM date)'), 'BETWEEN', new \Sql(($year - 2).' AND '.($year + 2)))
			->group(new \Sql('m1_year'))
			->sort(new \Sql('m1_year DESC'))
			->getCollection();

	}

}
?>
