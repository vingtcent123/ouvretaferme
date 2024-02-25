<?php
(new \farm\ActionPage())
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

(new \farm\ActionPage())
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
	->doDelete(fn($data) => throw new ViewAction($data))
	->read('analyzeTime', function($data) {

		$data->year = GET('year', 'int', date('Y'));
		$data->eCategory = \farm\CategoryLib::getByFarm($data->eFarm, id: GET('category'));

		$data->cActionTimesheet = \farm\AnalyzeLib::getActionTimesheet($data->e, $data->eCategory, $data->year);
		[$data->cTimesheetMonth, $data->cTimesheetUser] = \farm\AnalyzeLib::getActionMonths($data->e, $data->eCategory, $data->year);

		throw new ViewAction($data);

	});

(new Page())
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cCategory = \farm\CategoryLib::getByFarm($data->eFarm, index: 'id');
		$data->cAction = \farm\ActionLib::getForManage($data->eFarm);

		\farm\FarmerLib::register($data->eFarm);

		throw new \ViewAction($data);

	});
?>
