<?php
(new \selling\ProductPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		return new \selling\Product([
			'farm' => $data->eFarm
		]);

	})
	->create()
	->doCreate(function($data) {
		throw new ReloadAction('selling', 'Product::created');
	});

(new \selling\ProductPage())
	->read('/produit/{id}', function($data) {

		$data->eFarm = $data->e['farm'];
		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		\farm\FarmerLib::register($data->eFarm);

		$data->cGrid = \selling\GridLib::getByProduct($data->e);

		$data->cItemLast = \selling\ItemLib::getByProduct($data->e);
		$data->cItemTurnover = \selling\AnalyzeLib::getProductTurnover($data->e);

		throw new ViewAction($data);

	})
	->read('analyze', function($data) {

		$data->e['farm']->validate('canAnalyze');

		$data->search = new Search([
			'type' => \selling\Customer::GET('type', 'type'),
		], REQUEST('sort'));

		$data->year = GET('year', 'int', date('Y'));

		$data->cItemTurnover = \selling\AnalyzeLib::getProductTurnover($data->e, $data->year, $data->search);

		$data->cItemCustomer = \selling\AnalyzeLib::getProductCustomers($data->e, $data->year, $data->search);
		$data->cItemType = \selling\AnalyzeLib::getProductTypes($data->e, $data->year, $data->search);
		$data->cItemMonth = \selling\AnalyzeLib::getProductMonths($data->e, $data->year, $data->search);
		$data->cItemMonthBefore = \selling\AnalyzeLib::getProductMonths($data->e, $data->year - 1, $data->search);
		$data->cItemWeek = \selling\AnalyzeLib::getProductWeeks($data->e, $data->year, $data->search);
		$data->cItemWeekBefore = \selling\AnalyzeLib::getProductWeeks($data->e, $data->year - 1, $data->search);

		throw new ViewAction($data);

	})
	->read('updateGrid', function($data) {

		$data->e['farm']['selling'] = \selling\ConfigurationLib::getByFarm($data->e['farm']);

		$data->cCustomer = \selling\CustomerLib::getForGrid($data->e);

		throw new ViewAction($data);

	})
	->write('doUpdateGrid', function($data) {

		$data->cGrid = \selling\GridLib::prepareByProduct($data->e, $_POST);

		\selling\GridLib::updateGrid($data->cGrid);

		throw new ViewAction();

	})
	->write('doDeleteGrid', function($data) {

		\selling\GridLib::deleteByProduct($data->e);

		throw new ReloadLayerAction();

	})
	->quick(['privatePrice', 'privateStep', 'proPrice', 'proPackaging'])
	->update(function($data) {

		$data->e['farm']['selling'] = \selling\ConfigurationLib::getByFarm($data->e['farm']);

		throw new ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('selling', 'Product::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->doDelete(fn() => throw new ReloadAction('selling', 'Product::deleted'));

(new Page())
	->post('query', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm', '?int'))->validate('canWrite');;
		$type = POST('type', '?string');

		$data->cProduct = \selling\ProductLib::getFromQuery(POST('query'), $eFarm, $type);

		throw new \ViewAction($data);

	});
?>
