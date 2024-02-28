<?php
// Affichage des participants pour une tâche
(new Page(function($data) {

		$data->cTask = \series\TaskLib::getByIds(REQUEST('ids', 'array'), properties: \series\Task::getSelection() + [
			'cccPlace' => \series\PlaceLib::delegateByTask()
		]);

		\series\Task::validateBatch($data->cTask);
		\series\Task::validateSameAction($data->cTask);

	}))
	->get('index', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = $data->cTask->first()['farm'];
		$data->eFarm->validate('hasFeatureTime');

		\farm\FarmerLib::register($data->eFarm);

		$data->cUser = \farm\FarmerLib::getUsersByFarmForTasks(
			$data->eFarm,
			$data->cTask,
			withPresenceAbsence: TRUE
		);

		$data->eUserSelected = $data->cUser->search(GET('user', 'user\User', $data->eUserOnline), new \user\User());

		(new \hr\WorkingTime([
			'farm' => $data->eFarm,
			'user' => $data->eUserSelected
		]))->validate('canRead');

		\series\TimesheetLib::fillTimesByTasks($data->cUser, $data->cTask);

		if($data->eUserSelected->empty()) {
			throw new NotExpectedAction('Invalid user');
		}

		$data->eUserSelected['ccTimesheet'] = \series\TimesheetLib::getByUserAndTasks($data->eUserSelected, $data->cTask);

		$date = \series\Timesheet::GET('date', 'date', currentDate());

		$data->eTimesheet = new \series\Timesheet([
			'date' => $date
		]);

		\series\TaskLib::fillHarvestDates($data->cTask);

		throw new \ViewAction($data);

	});

// Modification d'un participant
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->cTask = \series\TaskLib::getByIds(REQUEST('ids', 'array'));
		\series\Task::validateBatch($data->cTask);
		\series\Task::validateSameAction($data->cTask);

		$data->eFarm = $data->cTask->first()['farm'];
		$data->eFarm->validate('hasFeatureTime');

		$data->cUser = \farm\FarmerLib::getUsersByFarmForTasks(
			$data->eFarm,
			$data->cTask
		);

		$data->eUserSelected = $data->cUser->search(POST('user', 'user\User'), new \user\User());

		if($data->eUserSelected->empty()) {
			throw new NotExpectedAction('Invalid user');
		}

	}))
	->post('doUpdateUser', function($data) {

		$fw = new FailWatch;

		$eTimesheet = new \series\Timesheet([
			'farm' => $data->eFarm,
			'user' => $data->eUserSelected
		]);

		$eTimesheet->validate('canWrite');

		$eTimesheet->build(['date'], $_POST, for: 'update');

		$fw->validate();

		\series\TimesheetLib::writeByTasks(
			$data->cTask,
			$eTimesheet,
			POST('timeAdd', 'float', 0.0),
			POST('distribution', ['area', 'plant', 'fair', 'harvest'], 'fair')
		);

		$fw->validate();

		throw new ViewAction($data, ':update');

	})
	->post('doDeleteUser', function($data) {

		(new \series\Timesheet([
			'farm' => $data->eFarm,
			'user' => $data->eUserSelected
		]))->validate('canDelete');

		\series\TimesheetLib::deleteByUser($data->eUserSelected, $data->cTask);

		throw new ViewAction($data, ':update');

	});

(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->cTimesheet = \series\TimesheetLib::getByIds(REQUEST('ids', 'array'))->validate('canWrite');

	}))
	->post('doDeleteCollection', function($data) {

		\series\TimesheetLib::deleteCollection($data->cTimesheet);

		throw new ViewAction($data, ':update');

	});
?>
