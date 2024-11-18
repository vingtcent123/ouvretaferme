<?php
(new \shop\DatePage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$data->eShop = \shop\ShopLib::getById(INPUT('shop'))->validateProperty('farm', $data->eFarm);

		return new \shop\Date([
			'farm' => $data->eFarm,
			'shop' => $data->eShop,
			'type' => $data->eShop['type']
		]);

	})
	->create(function($data) {

		\farm\FarmerLib::register($data->eFarm);

		$data->cProduct = \selling\ProductLib::getForShop($data->e['farm'], $data->e['type']);

		// Si c'est une copie : récupérer également la liste des produits de la date en question
		$data->eDateBase = \shop\DateLib::getById(GET('date'));

		if($data->eDateBase->notEmpty()) {
			$data->eDateBase->validate('canRead');
			$data->eDateBase['cProduct'] = \shop\ProductLib::getForCopy($data->eShop, $data->eDateBase);
		}


		$data->e['cCatalog'] = \shop\CatalogLib::getByFarm($data->eFarm, type: $data->e['type']);
		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->eFarm, index: 'id');
		$data->e['ccPoint'] = \shop\PointLib::getByFarm($data->eFarm);

		throw new \ViewAction($data);

	})
	->doCreate(fn($data) => throw new RedirectAction(\shop\ShopUi::adminDateUrl($data->e['farm'], $data->e['shop'], $data->e).'?success=shop:'.(GET('copied', 'bool') ? 'Date::created' : 'Date::copied')))
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->read('/ferme/{farm}/boutique/{shop}/date/{id}', function($data) {

		$data->eShop = \shop\ShopLib::getById(GET('shop'))->validate('canRead');

		if($data->e['shop']['id'] !== $data->eShop['id']) {
			throw new NotExpectedAction('Inconsistency');
		}

		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->eShop['farm']);

		\shop\DateLib::applySales($data->e);

		$data->eFarm = \farm\FarmLib::getById($data->eShop['farm']);

		\farm\FarmerLib::setView('viewShop', $data->eFarm, \farm\Farmer::SHOP);

		\farm\FarmerLib::register($data->eFarm);

		$data->e['farm'] = $data->eFarm;

		$data->e['cProduct'] = \shop\ProductLib::getByDate($data->e, onlyActive: FALSE);

		$data->cSale = \selling\SaleLib::getByDate($data->e, NULL, select: \selling\Sale::getSelection() + [
			'shopPoint' => \shop\PointElement::getSelection()
		]);

		$data->e['cProduct']->mergeCollection(\shop\ProductLib::aggregateBySales($data->cSale, $data->e['cProduct']->getColumnCollection('product')));
		$data->e['cProduct']->sort(['product' => ['name']], natural: TRUE);

		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->eFarm);
		$data->e['ccPoint'] = \shop\PointLib::getByDate($data->e);

		if($data->e['catalogs']) {
			$data->e['cCatalog'] = \shop\CatalogLib::getByIds($data->e['catalogs'], index: 'id');
		} else {
			$data->e['cCatalog'] = new Collection();
		}

		throw new \ViewAction($data);

	})
	->write('doUpdatePoint', function($data) {

		$data->ePoint = \shop\PointLib::getById(POST('point'))
			->validate('isActive')
			->validateProperty('farm', $data->e['farm']);

		$fw = new FailWatch();

		\shop\DateLib::updatePoint($data->e, $data->ePoint, POST('status', 'bool'));

		$fw->validate();

		throw new ViewAction($data);

	})
	->read('downloadSales', function($data) {

		$data->cSale = \selling\SaleLib::getForLabelsByDate($data->e);

		if($data->cSale->empty()) {
			throw new NotExpectedAction('No sale');
		}

		$content = \selling\PdfLib::build('/shop/date:getSales?id='.$data->e['id']);
		$filename = 'sales-'.$data->e['id'].'.pdf';

		throw new PdfAction($content, $filename);

	})
	->doDelete(fn($data) => throw new RedirectAction(\shop\ShopUi::adminUrl($data->e['farm'], $data->e['shop']).'&success=shop:Date::deleted'));

(new \shop\DatePage())
	->applyElement(function($data, \shop\Date $eDate) {

		$eDate['shop'] = \shop\ShopLib::getById($eDate['shop']);

	})
	->update(function($data) {

		$data->e['ccPoint'] = \shop\PointLib::getByFarm($data->e['farm']);

		throw new \ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('shop', 'Date::updated'));

(new Page())
	->get('getSales', function($data) {

		$data->e = \shop\DateLib::getById(GET('id'))->validate('canRemote');
		$data->e['shop'] = \shop\ShopLib::getById($data->e['shop']);

		$data->cSale = \selling\SaleLib::getForLabelsByDate($data->e, selectItems: TRUE, selectPoint: TRUE);
		$data->cItem = \selling\ItemLib::getSummaryByDate($data->e);

		throw new ViewAction($data);

	});