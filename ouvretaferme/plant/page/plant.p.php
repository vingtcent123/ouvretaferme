<?php
(new \plant\PlantPage())
	->applyElement(function($data, \plant\Plant $e) {

		$e->validate('canRead');

		$data->eFarm = \farm\FarmLib::getById($e['farm']);

	})
	->read('/espece/{id@int}', function($data) {

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);

		$data->cCrop = \production\CropLib::getByFarm($data->eFarm, $data->cActionMain, FALSE, search: new Search([
			'plant' => $data->e
		]));

		$data->cProduct = \selling\ProductLib::getByPlant($data->e);

		$data->cItemTurnover = \selling\AnalyzeLib::getProductsTurnover($data->cProduct);

		\farm\FarmerLib::register($data->eFarm);

		throw new ViewAction($data);

	})
	->read('analyzeSales', function($data) {

		$data->eFarm->validate('canAnalyze');

		$data->search = new Search([
			'type' => \selling\Customer::GET('type', 'type'),
		], REQUEST('sort'));

		$data->year = GET('year', 'int', date('Y'));

		$data->cProduct = \selling\ProductLib::getByPlant($data->e);

		$cItem = \selling\AnalyzeLib::getPlants($data->year, search: new Search([
			'plant' => $data->e
		]));

		if($cItem->notEmpty()) {
			$data->e['cItem'] = $cItem[$data->e['id']]['cItem'];
		}

		$data->cItemTurnover = \selling\AnalyzeLib::getProductsTurnover($data->cProduct, $data->year, $data->search);

		$data->cItemCustomer = \selling\AnalyzeLib::getProductsCustomers($data->cProduct, $data->year, $data->search);
		$data->cItemType = \selling\AnalyzeLib::getProductsTypes($data->cProduct, $data->year, $data->search);
		$data->cItemMonth = \selling\AnalyzeLib::getProductsMonths($data->cProduct, $data->year, $data->search);
		$data->cItemMonthBefore = \selling\AnalyzeLib::getProductsMonths($data->cProduct, $data->year - 1, $data->search);
		$data->cItemWeek = \selling\AnalyzeLib::getProductsWeeks($data->cProduct, $data->year, $data->search);
		$data->cItemWeekBefore = \selling\AnalyzeLib::getProductsWeeks($data->cProduct, $data->year - 1, $data->search);

		throw new ViewAction($data);

	})
	->read('analyzeTime', function($data) {

		$data->eFarm->validate('canAnalyze');

		$data->year = GET('year', 'int', date('Y'));

		$data->cPlantTimesheet = \series\AnalyzeLib::getPlantTimesheet($data->e, $data->year);
		[$data->cTimesheetByAction, $data->cTimesheetByUser] = \series\AnalyzeLib::getActionTimesheetByPlant($data->e, $data->year);

		$data->cPlantMonth = \series\AnalyzeLib::getPlantMonths($data->e, $data->year);

		throw new ViewAction($data);

	});

(new \plant\PlantPage(function($data) {

		\user\ConnectionLib::checkLogged();

	}))
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \plant\Plant([
			'farm' => $data->eFarm,
		]);

	})
	->create(function($data) {

		$data->cFamily = \plant\FamilyLib::getList();

		throw new \ViewAction($data);

	})
	->doCreate(fn($data) => throw new ViewAction($data));

(new \plant\PlantPage(function($data) {

		\user\ConnectionLib::checkLogged();

	}))
	->applyElement(function($data, \plant\Plant $e) {

		$e->validate('canWrite');

		$data->eFarm = $e['farm'];

		\farm\Farm::model()
			->select('status', 'name')
			->get($data->eFarm);

		$data->eFarm->validate('active');

		$e['cFamily'] = \plant\FamilyLib::getList();

	})
	->doUpdateProperties('doUpdateStatus', ['status'], function($data) {
		throw new ViewAction($data);
	})
	->update(fn($data) => throw new ViewAction($data))
	->doUpdate(fn($data) => throw new ViewAction($data))
	->doDelete(fn($data) => throw new ViewAction($data));
?>
