<?php
(new \hr\WorkingTimePage(function($data) {

		\user\ConnectionLib::checkLogged();

	}))
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$data->eUserTime = \user\UserLib::getById(INPUT('user'));

		return new \hr\WorkingTime([
			'farm' => $data->eFarm,
			'user' => $data->eUserTime
		]);

	})
	->doCreate(function($data) {
		throw new ReloadAction();
	});

(new Page())
	->post('getByUser', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canPlanning');
		$data->week = \series\Task::POST('week', 'plannedWeek', function($value) {
			throw new NotExpectedAction('Invalid week \''.encode($value).'\'');
		});

		$data->cUserFarm = \farm\FarmerLib::getUsersByFarmForPeriod(
			$data->eFarm,
			week_date_starts($data->week),
			week_date_ends($data->week),
			withPresenceAbsence: TRUE
		);

		$data->eUserTime = \user\UserLib::getById(POST('user'))->validate('active');
		$data->eUserTime['cPresence'] = $data->cUserFarm[$data->eUserTime['id']]['cPresence'] ?? new Collection();
		$data->eUserTime['cAbsence'] = $data->cUserFarm[$data->eUserTime['id']]['cAbsence'] ?? new Collection();

		if($data->cUserFarm->offsetExists($data->eUserTime['id']) === FALSE) {
			throw new NotExpectedAction('Bad user');
		}

		(new \hr\WorkingTime([
			'farm' => $data->eFarm,
			'user' => $data->eUserTime
		]))->validate('canRead');

		$data->eUserTime['weekTimesheet'] = \series\TimesheetLib::getTimesByWeek($data->eFarm, $data->eUserTime, $data->week);
		$data->eUserTime['cWorkingTimeWeek'] = \hr\WorkingTimeLib::getByWeek($data->eFarm, $data->eUserTime, $data->week);


		throw new ViewAction($data);

	})
?>
