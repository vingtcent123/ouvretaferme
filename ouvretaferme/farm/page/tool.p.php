<?php
(new \farm\ToolPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm', '?int'));

	}))
	->getCreateElement(function($data) {

		return \farm\ToolLib::getNewTool($data->eFarm, REQUEST('routineName', array_keys(\farm\RoutineLib::list())));

	})
	->create(function($data) {

		$data->e['cAction'] = \farm\ActionLib::getByFarm($data->eFarm);

		throw new ViewAction($data);
	})
	->doCreate(fn($data) => throw new ViewAction($data));

(new \farm\ToolPage())
	->applyElement(function($data, \farm\Tool $e) {

		$e->validate('canWrite');

		$data->eFarm = $e['farm'];

		\farm\Farm::model()
			->select('status', 'name')
			->get($data->eFarm);

		$data->eFarm->validate('active');

	})
	->quick(['name', 'action', 'stock'], [
		'action' => function($data) {
			$data->e['cAction'] = \farm\ActionLib::getByFarm($data->e['farm']);
		}
	])
	->update(function($data) {

		$data->e['cAction'] = \farm\ActionLib::getByFarm($data->eFarm);

		$data->routines = \farm\RoutineLib::getByAction($data->e['action']);

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new ViewAction($data))
	->doUpdateProperties('doUpdateStatus', ['status'], function() {
		throw new ReloadAction('farm', 'Tool::updated');
	})
	->doDelete(fn($data) => throw new ViewAction($data));

(new Page())
	->get('/outil/{id@int}', function($data) {

		$data->eTool = \farm\ToolLib::getById(REQUEST('id'))->validate('canRead');

		$data->eFarm = \farm\FarmLib::getById($data->eTool['farm']);
		\farm\FarmerLib::register($data->eFarm);

		throw new ViewAction($data);

	})
	->post('query', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');
		$eAction = \farm\ActionLib::getById(POST('action'));

		$data->cTool = \farm\ToolLib::getFromQuery(POST('query'), $eFarm, $eAction);

		throw new \ViewAction($data);

	})
	->post('getRoutinesField', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');
		$eAction = \farm\ActionLib::getById(POST('action'))->validate('canRead');

		$data->eTool = new \farm\Tool([
			'farm' => $eFarm,
			'action' => $eAction,
			'routineName' => NULL,
			'routineValue' => []
		]);

		$data->routines = \farm\RoutineLib::getByAction($eAction, FALSE);

		throw new \ViewAction($data);

	})
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');

		$data->search = new Search([
			'name' => GET('name'),
			'action' => GET('action', 'farm\Action'),
			'status' => GET('status', default: \farm\Tool::ACTIVE),
		]);

		$data->routineName = GET('routineName');

		if(
			$data->routineName === NULL or
			\farm\RoutineLib::exists($data->routineName) === FALSE or
			\farm\RoutineLib::get($data->routineName)['standalone'] === FALSE
		) {
			$data->routineName = NULL;
		}

		$data->tools = \farm\ToolLib::countByFarm($data->eFarm, $data->routineName);

		if(
			get_exists('status') and
			$data->tools[\plant\Plant::INACTIVE] === 0
		) {
			throw new RedirectAction(\farm\ToolUi::manageUrl($data->eFarm, $data->routineName));
		}

		$data->eToolNew = \farm\ToolLib::getNewTool($data->eFarm, $data->routineName);
		$data->eToolNew['cAction'] = \farm\ActionLib::getByFarm($data->eFarm);

		$data->cTool = \farm\ToolLib::getByFarm($data->eFarm, routineName: $data->routineName, search: $data->search);

		$data->cActionUsed = \farm\ToolLib::getActionsByFarm($data->eFarm);

		\farm\FarmerLib::register($data->eFarm);

		throw new \ViewAction($data);

	});
?>
