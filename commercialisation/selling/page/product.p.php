<?php
new \selling\ProductPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validateTax();

		return new \selling\Product([
			'farm' => $data->eFarm,
			'status' => \selling\Product::ACTIVE
		]);

	})
	->create(function($data) {

		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm']);
		$data->e['cUnit'] = \selling\UnitLib::getByFarm($data->e['farm']);

		if(get_exists('from')) {

			$data->e->merge(
				\selling\ProductLib::getById(GET('from'))
					->validateProperty('farm', $data->eFarm)
					->validate('acceptDuplicate')
			);
			$data->e['status'] = \shop\Product::ACTIVE;

		} else {

			$data->e->merge([
				'profile' => \selling\Product::GET('profile', 'profile'),
				'quality' => $data->eFarm['quality'],
				'vat' => $data->eFarm->getConf('defaultVat'),
				'private' => TRUE,
				'pro' => TRUE,
				'unit' => new \selling\Unit(),
			]);

			switch($data->e['profile']) {

				case \selling\Product::UNPROCESSED_PLANT :
					$data->e['unit'] = \selling\UnitLib::getByFqn('kg');
					break;

				case \selling\Product::PROCESSED_PRODUCT :
				case \selling\Product::PROCESSED_FOOD :
					$data->e['unit'] = \selling\UnitLib::getByFqn('unit');
					break;

				case \selling\Product::COMPOSITION :
					$data->e['unit'] = \selling\UnitLib::getByFqn('unit');
					$data->e['private'] = FALSE;
					$data->e['pro'] = FALSE;
					break;

			}

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

		if($eProduct['farm']->hasAccounting()) {

			\company\CompanyLib::connectSpecificDatabaseAndServer($eProduct['farm']);

			if($eProduct['proAccount']->notEmpty()) {
				$eProduct['proAccount'] = \account\AccountLib::getById($eProduct['proAccount']['id']);
			}
			if($eProduct['privateAccount']->notEmpty()) {
				$eProduct['privateAccount'] = \account\AccountLib::getById($eProduct['privateAccount']['id']);
			}

		}

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
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlSellingProducts($data->e['farm']).'?success=selling:Product::deleted'));

new \selling\ProductPage()
	->applyElement(function($data, \selling\Product $eProduct) {

		if($eProduct['privatePriceInitial'] !== NULL) {
			$eProduct['privatePriceDiscount'] = $eProduct['privatePrice'];
		}
		if($eProduct['proPriceInitial'] !== NULL) {
			$eProduct['proPriceDiscount'] = $eProduct['proPrice'];
		}

		if($eProduct['farm']->hasAccounting()) {
			\company\CompanyLib::connectSpecificDatabaseAndServer($eProduct['farm']);
				$eProduct['proAccount'] = $eProduct['proAccount']->notEmpty() ? \account\AccountLib::getById($eProduct['proAccount']['id']) : $eProduct['proAccount'];
				$eProduct['privateAccount'] = $eProduct['privateAccount']->notEmpty() ? \account\AccountLib::getById($eProduct['privateAccount']['id']) : $eProduct['privateAccount'];
		}

	})
	->quick([
		'privatePrice' => ['privatePrice', 'privatePriceDiscount'],
		'privateStep',
		'proPrice' => ['proPrice', 'proPriceDiscount'],
		'proPackaging',
		'proStep',
		'proAccount', 'privateAccount',
	]);

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
		$withComposition = POST('withComposition', 'bool', TRUE);
		$exclude = post_exists('exclude') ? explode(',', POST('exclude')) : [];

		$data->cProduct = \selling\ProductLib::getFromQuery(POST('query'), $eFarm, $type, excludeIds: $exclude, stock: $stock, withComposition: $withComposition);

		throw new \ViewAction($data);

	});

new Page()
	->get('updateAccount', function($data) {

		$ids = GET('ids', 'array');

		$data->cProduct = \selling\ProductLib::getByIds($ids);

		$data->eFarm = $data->cProduct->first()['farm']->validate('canManage');

		throw new \ViewAction($data);

	});

new \selling\ProductPage()
	->applyCollection(function($data, Collection $c) {

		$eFarm = $c->first()['farm'];
		$c->validateProperty('farm', $eFarm);
		\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);
	})
	->doUpdateCollectionProperties('doUpdateAccountCollection', ['proAccount', 'privateAccount'], fn($data) => throw new ReloadAction('selling', 'Product::updatedSeveral'));

?>
