<?php
new \farm\FarmPage()
	->read('/ferme/{id}/date/{date}', function($data) {

		\farm\FarmerLib::setView('viewShop', $data->e, \farm\Farmer::SHOP);

		$data->eDate = \shop\DateLib::getById(GET('date'));

		$data->eShop = \shop\ShopLib::getById($data->eDate['shop'])->validateShareRead($data->e);
		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->eShop['farm']);

		\shop\DateLib::applySales($data->eDate);


		$data->cSale = \selling\SaleLib::getByDate($data->eDate, NULL, select: \selling\Sale::getSelection() + [
			'shopPoint' => \shop\PointElement::getSelection()
		]);

		$data->eDate['farm'] = $data->eShop['farm'];
		$data->eDate['shop'] = $data->eShop;

		$data->eDate['cProduct'] = \shop\ProductLib::getByDate($data->eDate);
		$data->eDate['cCustomer'] = \selling\CustomerLib::getLimitedByProducts($data->eDate['cProduct']);

		// Uniquement les boutiques avec un seul producteur
		$data->eDate['cProduct']->mergeCollection(\shop\ProductLib::aggregateBySales($data->cSale, $data->eDate['cProduct']->getColumnCollection('product')));

		$data->eDate['cCategory'] = \selling\CategoryLib::getByFarm($data->e);
		$data->eDate['ccPoint'] = \shop\PointLib::getByDate($data->eDate);

		if($data->eDate['catalogs']) {
			$data->eDate['cCatalog'] = \shop\CatalogLib::getByIds($data->eDate['catalogs']);
			$data->eDate['cFarm'] = \farm\FarmLib::getByIds($data->eDate['cCatalog']->getColumnCollection('farm'), index: 'id');
		} else {
			$data->eDate['cCatalog'] = new Collection();
			$data->eDate['cFarm'] = new Collection([
				$data->e['id'] => $data->e
			]);
		}

		if($data->eShop['shared']) {
			$data->eShop['cShare'] = \shop\ShareLib::getByShop($data->eShop);
			$data->eShop['ccRange'] = \shop\RangeLib::getByShop($data->eShop);
			$data->eShop['cDepartment'] = \shop\DepartmentLib::getByShop($data->eShop);
		}

		$data->eFarm = $data->e;

		throw new \ViewAction($data);

	});

new \shop\DatePage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$data->eShop = \shop\ShopLib::getById(INPUT('shop'))->validateProperty('farm', $data->eFarm);

		return new \shop\Date([
			'farm' => $data->eFarm,
			'shop' => $data->eShop,
			'type' => $data->eShop['type'],
		]);

	})
	->create(function($data) {


		$data->cProduct = \selling\ProductLib::getForSale($data->e['farm'], $data->e['type']);

		// Si c'est une copie : récupérer également la liste des produits de la date en question
		$data->eDateBase = \shop\DateLib::getById(GET('date'));

		if($data->eDateBase->notEmpty()) {
			$data->eDateBase->validate('canRead');
			$data->eDateBase['cProduct'] = \shop\ProductLib::getForCopy($data->eShop, $data->eDateBase);
		}


		$data->e['cCatalog'] = \shop\CatalogLib::getForShop($data->eShop, $data->e['type'], $data->eDateBase);
		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->eFarm, index: 'id');
		$data->e['ccPoint'] = \shop\PointLib::getByFarm($data->eFarm);

		throw new \ViewAction($data);

	})
	->doCreate(fn($data) => throw new RedirectAction(\shop\ShopUi::adminDateUrl($data->e['farm'], $data->e).'?success=shop:'.(GET('copied', 'bool') ? 'Date::created' : 'Date::copied')))
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
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

new \shop\DatePage()
	->applyElement(function($data, \shop\Date $eDate) {

		$eDate['shop'] = \shop\ShopLib::getById($eDate['shop']);

	})
	->write('doUpdateCatalog', function($data) {

		$newStatus = POST('status', 'bool');

		$data->eCatalog = \shop\CatalogLib::getById(POST('catalog'));

		// On ne vérifie l'existence du catalogue qu'en cas d'ajout à la vente
		if($newStatus) {
			$data->eCatalog->validateShop($data->e['shop']);
		}

		$fw = new FailWatch();

		\shop\DateLib::updateCatalog($data->e, $data->eCatalog, $newStatus);

		$fw->validate();

		throw new ViewAction($data);

	})
	->update(function($data) {

		$data->e['ccPoint'] = \shop\PointLib::getByFarm($data->e['farm']);

		throw new \ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('shop', 'Date::updated'));

new Page()
	->get('getSales', function($data) {

		$data->e = \shop\DateLib::getById(GET('id'))->validate('canRemote');
		$data->e['shop'] = \shop\ShopLib::getById($data->e['shop']);

		$data->cSale = \selling\SaleLib::getForLabelsByDate($data->e, selectItems: TRUE, selectPoint: TRUE);
		$data->cItem = \selling\ItemLib::getSummaryByDate($data->e);

		throw new ViewAction($data);

	});