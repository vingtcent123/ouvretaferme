<?php
(new \selling\ProductPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \selling\Product([
			'farm' => $data->eFarm,
			'status' => \selling\Product::ACTIVE
		]);

	})
	->create(function($data) {

		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm']);
		$data->e['cUnit'] = \selling\UnitLib::getByFarm($data->e['farm']);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		$category = $data->e['category']->empty() ? '' : $data->e['category']['id'];
		throw new RedirectAction(\selling\ProductUi::url($data->e).'?category='.$category.'&success=selling:Product::created');
	});

(new \selling\ProductPage())
	->read('/produit/{id}', function($data) {

		if($data->e['category']->notEmpty()) {
			$data->e['category'] = \selling\CategoryLib::getById($data->e['category']);
		}

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		\farm\FarmerLib::register($data->eFarm);

		$data->cGrid = \selling\GridLib::getByProduct($data->e);

		$data->cItemLast = \selling\ItemLib::getByProduct($data->e);
		$data->cItemYear = \selling\AnalyzeLib::getProductYear($data->e);

		throw new ViewAction($data);

	})
	->read('analyze', function($data) {

		$data->e['farm']->validate('canAnalyze');

		$data->search = new Search([
			'type' => \selling\Customer::GET('type', 'type'),
		], REQUEST('sort'));

		$data->year = GET('year', 'int', date('Y'));

		$data->cItemYear = \selling\AnalyzeLib::getProductYear($data->e, $data->year, $data->search);

		$data->cItemCustomer = \selling\AnalyzeLib::getProductCustomers($data->e, $data->year, $data->search);
		$data->cItemType = \selling\AnalyzeLib::getProductTypes($data->e, $data->year, $data->search);
		$data->cItemMonth = \selling\AnalyzeLib::getProductMonths($data->e, $data->year, $data->search);
		$data->cItemMonthBefore = \selling\AnalyzeLib::getProductMonths($data->e, $data->year - 1, $data->search);
		$data->cItemWeek = \selling\AnalyzeLib::getProductWeeks($data->e, $data->year, $data->search);
		$data->cItemWeekBefore = \selling\AnalyzeLib::getProductWeeks($data->e, $data->year - 1, $data->search);

		throw new ViewAction($data);

	})
	->read('updateGrid', function($data) {

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
	->write('doEnableStock', function($data) {

		$data->e->validate('acceptEnableStock');

		\selling\StockLib::enable($data->e);

		throw new ReloadAction('selling', 'Product::stockEnabled');

	})
	->write('doDisableStock', function($data) {

		$data->e->validate('acceptDisableStock');

		\selling\StockLib::disable($data->e);

		throw new ReloadAction('selling', 'Product::stockDisabled');

	})
	->quick(['privatePrice', 'privateStep', 'proPrice', 'proPackaging', 'proStep'])
	->update(function($data) {

		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm']);

		throw new ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('selling', 'Product::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlSellingProduct($data->e['farm']).'?success=selling:Product::deleted'));

(new \selling\ProductPage())
	->applyCollection(function($data, Collection $c) {
		$c->validateProperty('farm', $c->first()['farm']);
	})
	->doUpdateCollectionProperties('doUpdateStatusCollection', ['status'], fn($data) => throw new ReloadAction())
	->doUpdateCollectionProperties('doUpdateCategoryCollection', ['category'], fn($data) => throw new ReloadAction('selling', 'Product::categoryUpdated'));

(new Page())
	->post('query', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm', '?int'))->validate('canWrite');;
		$type = POST('type', '?string');
		$stock = POST('stock', '?string');

		$data->cProduct = \selling\ProductLib::getFromQuery(POST('query'), $eFarm, $type, $stock);

		throw new \ViewAction($data);

	});
?>
