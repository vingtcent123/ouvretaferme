<?php
new \farm\ActionPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \farm\Action([
			'farm' => $data->eFarm,
		]);

	})
	->create(function($data) {

		$data->cCategory = \farm\CategoryLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->doCreate(fn($data) => throw new ViewAction($data));

new \farm\ActionPage()
	->applyElement(function($data, \farm\Action $e) {

		$e->validate('canWrite');

		$data->eFarm = $e['farm'];

		\farm\Farm::model()
			->select('status', 'name')
			->get($data->eFarm);

		$data->eFarm->validate('active');

	})
	->update(function($data) {

		$data->e['cCategory'] = \farm\CategoryLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new ViewAction($data))
	->doDelete(fn($data) => throw new ViewAction($data));

new Page()
	->get('analyzeTime', function($data) {

		$data->year = GET('year', 'int', date('Y'));

		$data->eCategory = \farm\CategoryLib::getById(GET('category'));
		$data->eCategory['farm']->validate('canAnalyze');

		$data->eAction = \farm\ActionLib::getById(GET('action'));

		if($data->eAction->notEmpty()) {
			$data->eAction->validateProperty('farm' , $data->eCategory['farm']);
		}

		$data->cTimesheetTarget = \farm\AnalyzeLib::getActionTimesheet($data->eAction, $data->eCategory, $data->year);
		[$data->cTimesheetMonth, $data->cTimesheetUser] = \farm\AnalyzeLib::getActionMonths($data->eAction, $data->eCategory, $data->year);
		[$data->cTimesheetMonthBefore] = \farm\AnalyzeLib::getActionMonths($data->eAction, $data->eCategory, $data->year - 1);

		throw new ViewAction($data);

	})
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cCategory = \farm\CategoryLib::getByFarm($data->eFarm, index: 'id');
		$data->cAction = \farm\ActionLib::getForManage($data->eFarm);


		throw new \ViewAction($data);

	});
?>
