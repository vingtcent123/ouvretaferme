<?php
new \selling\ProductPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \selling\Product([
			'farm' => $data->eFarm,
			'status' => \selling\Product::ACTIVE,
			'composition' => INPUT('composition', 'bool')
		]);

	})
	->create(function($data) {

		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm']);
		$data->e['cUnit'] = \selling\UnitLib::getByFarm($data->e['farm']);

		if(get_exists('from')) {

			$data->e->merge(\selling\ProductLib::getById(GET('from'))->validateProperty('farm', $data->eFarm));
			$data->e['status'] = \shop\Product::ACTIVE;

		} else {
			$data->e->merge([
				'quality' => $data->eFarm['quality'],
				'vat' => $data->eFarm->getSelling('defaultVat'),
				// L'utilisateur doit explicitement choisir la clientèle pour les produits composés
				'private' => $data->e['composition'] === FALSE,
				'pro' => $data->e['composition'] === FALSE,
				'unit' => $data->e['cUnit']->find(fn($eUnit) => $eUnit['fqn'] === ($data->e['composition'] ? 'unit' : 'kg'), limit: 1),
			]);
		}

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		$category = $data->e['category']->empty() ? '' : $data->e['category']['id'];
		throw new RedirectAction(\selling\ProductUi::url($data->e).'?category='.$category.'&success=selling:Product::created');
	});

new \selling\ProductPage()
	->applyElement(function($data, \selling\Product $eProduct) {

		$eProduct['cCategory'] = \selling\CategoryLib::getByFarm($eProduct['farm']);
		$eProduct['cUnit'] = \selling\UnitLib::getByFarmWithoutWeight($eProduct['farm']);

	})
	->update(fn($data) => throw new ViewAction($data))
	->doUpdate(fn() => throw new ReloadAction('selling', 'Product::updated'));

new \selling\ProductPage()
	->read('/produit/{id}', function($data) {

		if($data->e['category']->notEmpty()) {
			$data->e['category'] = \selling\CategoryLib::getById($data->e['category']);
		}

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		$data->cSaleComposition = \selling\SaleLib::getByComposition($data->e);
		$data->cGrid = \selling\GridLib::getByProduct($data->e);

		$data->switchComposition = \selling\ItemLib::containsProductIngredient($data->e);

		$data->cItemLast = \selling\ItemLib::getByProduct($data->e);
		$data->cItemYear = \selling\AnalyzeLib::getProductYear($data->eFarm, $data->e);

		throw new ViewAction($data);

	})
	->read('analyze', function($data) {

		$data->e['farm']->validate('canAnalyze');

		$data->search = new Search([
			'type' => \selling\Customer::GET('type', 'type')
		], REQUEST('sort'));

		$data->year = GET('year', 'int', date('Y'));

		if(in_array(GET('chart'), [\farm\Farmer::TURNOVER, \farm\Farmer::QUANTITY])) {
			\farm\FarmerLib::setView('viewAnalyzeChart', $data->e['farm'], GET('chart'));
		}

		$data->switchComposition = \selling\ItemLib::containsProductIngredient($data->e);

		$data->cItemYear = \selling\AnalyzeLib::getProductYear($data->e['farm'], $data->e, $data->year, $data->search);

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

		$fw = new FailWatch();

		$data->cGrid = \selling\GridLib::prepareByProduct($data->e, $_POST);

		$fw->validate();

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
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlSellingProduct($data->e['farm']).'?success=selling:Product::deleted'));

new \selling\ProductPage()
	->applyElement(function($data, \selling\Product $eProduct) {

		if($eProduct['privatePriceInitial'] !== NULL) {
			$eProduct['privatePriceDiscount'] = $eProduct['privatePrice'];
		}
		if($eProduct['proPriceInitial'] !== NULL) {
			$eProduct['proPriceDiscount'] = $eProduct['proPrice'];
		}

	})
	->quick(['privatePrice', 'privatePriceDiscount', 'privateStep', 'proPrice', 'proPriceDiscount', 'proPackaging', 'proStep']);

new \selling\ProductPage()
	->applyCollection(function($data, Collection $c) {
		$c->validateProperty('farm', $c->first()['farm']);
	})
	->doUpdateCollectionProperties('doUpdateStatusCollection', ['status'], fn($data) => throw new ReloadAction())
	->doUpdateCollectionProperties('doUpdateCategoryCollection', ['category'], fn($data) => throw new ReloadAction('selling', 'Product::categoryUpdated'));

new Page()
	->post('query', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm', '?int'))->validate('canWrite');;
		$type = POST('type', '?string');
		$stock = POST('stock', '?string');

		$data->cProduct = \selling\ProductLib::getFromQuery(POST('query'), $eFarm, $type, $stock);

		throw new \ViewAction($data);

	});
?>
