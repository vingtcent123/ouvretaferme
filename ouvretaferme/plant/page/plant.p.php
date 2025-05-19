<?php
new \farm\FarmPage()
	->read(['/ferme/{id}/especes', '/ferme/{id}/especes/{status}'], function($data) {

		$data->plants = \plant\PlantLib::countByFarm($data->e);

		if(
			get_exists('status') and
			$data->plants[\plant\Plant::INACTIVE] === 0
		) {
			throw new RedirectAction(\plant\PlantUi::urlManage($data->e));
		}

		$data->search = new Search([
			'cFamily' => \plant\FamilyLib::getList(),
			'id' => GET('plantId', '?int'),
			'family' => GET('family', 'plant\Family'),
			'status' => GET('status', default: \plant\Plant::ACTIVE),
		]);

		$data->cPlant = \plant\PlantLib::getByFarm($data->e, selectMetadata: TRUE, search: $data->search);

		if(
			$data->search->get('id') and
			$data->cPlant->notEmpty()
		) {
			$data->search->set('id', $data->cPlant->first());
		}

		// Pour le template Farm
		$data->eFarm = $data->e;

		throw new ViewAction($data, ':plant');

	}, validate: ['canWrite']);

new \plant\PlantPage()
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

		$data->cItemYear = \selling\AnalyzeLib::getProductsYear($data->eFarm, $data->cProduct);


		throw new ViewAction($data);

	})
	->read('analyzeSales', function($data) {

		$data->eFarm->validate('canAnalyze');

		$data->search = new Search([
			'type' => \selling\Customer::GET('type', 'type'),
			'type' => \selling\Customer::GET('type', 'type'),
		], REQUEST('sort'));

		$data->year = GET('year', 'int', date('Y'));

		$data->cProduct = \selling\ProductLib::getByPlant($data->e, index: 'id');

		$data->switchComposition = (
			$data->cProduct->contains(fn($eProduct) => $eProduct['composition']) or
			\selling\ItemLib::containsProductsIngredient($data->cProduct)
		);

		$cItem = \selling\AnalyzeLib::getPlants($data->eFarm, $data->year, search: new Search([
			'plant' => $data->e
		]));

		if($cItem->notEmpty()) {
			$data->e['cItem'] = $cItem[$data->e['id']]['cItem'];
		}

		$data->cItemTurnover = \selling\AnalyzeLib::getProductsTurnover($data->eFarm, $data->cProduct, $data->year, $data->search);
		$data->cItemTurnover->setColumn('product', fn($eItem) => $data->cProduct[$eItem['product']['id']]);

		$data->cItemYear = \selling\AnalyzeLib::getProductsYear($data->eFarm, $data->cProduct, $data->year, $data->search);

		$data->cItemCustomer = \selling\AnalyzeLib::getProductsCustomers($data->eFarm, $data->cProduct, $data->year, $data->search);
		$data->cItemType = \selling\AnalyzeLib::getProductsTypes($data->cProduct, $data->year, $data->search);
		$data->cItemMonth = \selling\AnalyzeLib::getProductsMonths($data->cProduct, $data->year, $data->search);
		$data->cItemMonthBefore = \selling\AnalyzeLib::getProductsMonths($data->cProduct, $data->year - 1, $data->search);
		$data->cItemWeek = \selling\AnalyzeLib::getProductsWeeks($data->eFarm, $data->cProduct, $data->year, $data->search);
		$data->cItemWeekBefore = \selling\AnalyzeLib::getProductsWeeks($data->eFarm, $data->cProduct, $data->year - 1, $data->search);

		throw new ViewAction($data);

	})
	->read('analyzeTime', function($data) {

		$data->eFarm->validate('canAnalyze');

		$data->year = GET('year', 'int', date('Y'));

		$data->cPlantTimesheet = \series\AnalyzeLib::getPlantTimesheet($data->e, $data->year);
		[$data->cTimesheetByAction, $data->cTimesheetByUser] = \series\AnalyzeLib::getActionTimesheetByPlant($data->e, $data->year);

		$data->cPlantMonth = \series\AnalyzeLib::getPlantMonths($data->e, $data->year);
		$data->cPlantMonthBefore = \series\AnalyzeLib::getPlantMonths($data->e, $data->year - 1);

		throw new ViewAction($data);

	});

new \plant\PlantPage(function($data) {

		\user\ConnectionLib::checkLogged();

	})
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

new \plant\PlantPage(function($data) {

		\user\ConnectionLib::checkLogged();

	})
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
