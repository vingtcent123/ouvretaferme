<?php
new \farm\FarmPage()
	->read('/ferme/{id}/date/{date}', function($data) {

		$data->eDate = \shop\DateLib::getById(GET('date'))->validate();
		$data->eShop = \shop\ShopLib::getById($data->eDate['shop'])->validateShare($data->e);

		if($data->eShop['opening'] === \shop\Shop::ALWAYS) {
			throw new RedirectAction(\shop\ShopUi::adminUrl($data->eShop['farm'], $data->eShop));
		}

		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->eShop['farm']);

		if($data->eShop['shared']) {

			$data->eShop['cShare'] = \shop\ShareLib::getByShop($data->eShop);
			$data->eShop['ccRange'] = \shop\RangeLib::getByShop($data->eShop);
			$data->eShop['cDepartment'] = \shop\DepartmentLib::getByShop($data->eShop);

		}

		\shop\DateLib::applyManagement($data->e, $data->eShop, $data->eDate, GET('page', 'int'));

		$data->eFarm = $data->e;

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);

		throw new \ViewAction($data);

	});

new \shop\DatePage()
	->getCreateElement(function($data) {

		$data->eShop = \shop\ShopLib::getById(INPUT('shop'))->validate('canWrite');
		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canSelling');

		if(
			$data->eShop['opening'] === \shop\Shop::ALWAYS and
			\shop\DateLib::getAlwaysByShop($data->eShop)->notEmpty()
		) {
			throw new RedirectAction(\shop\ShopUi::adminUrl($data->eFarm, $data->eShop));
		}

		return new \shop\Date([
			'farm' => $data->eShop['farm'],
			'shop' => $data->eShop,
			'type' => $data->eShop['type'],
		]);

	})
	->create(function($data) {

		$data->cProduct = \selling\ProductLib::getForSale($data->e['farm'], $data->e['type']);

		// Si c'est une copie : récupérer également la liste des produits de la date en question
		$data->eDateBase = \shop\DateLib::getById(GET('date'));


		if($data->eDateBase->notEmpty()) {

			$data->eDateBase->validateProperty('shop', $data->eShop);

			$data->eDateBase['cProduct'] = \shop\ProductLib::getForCopy($data->eShop, $data->eDateBase);

		}


		$data->e['cCatalog'] = \shop\CatalogLib::getForShop($data->eShop, $data->e['type'], $data->eDateBase);
		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm'], index: 'id');
		$data->e['ccPoint'] = \shop\PointLib::getByFarm($data->e['farm']);

		throw new \ViewAction($data);

	})
	->doCreate(fn($data) => throw new RedirectAction(\shop\ShopUi::adminDateUrl($data->e['farm'], $data->e).'?success=shop\\'.(GET('copied', 'bool') ? 'Date::created' : 'Date::copied')))
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

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$data->e['shop'] = \shop\ShopLib::getById($data->e['shop']);

		$data->e->canDownload($data->eFarm) ?: throw new NotAllowedAction();

		$data->cSale = \selling\SaleLib::getForLabelsByDate($data->eFarm, $data->e);

		if($data->cSale->empty()) {
			throw new NotExpectedAction('No sale');
		}

		$filename = 'sales-'.$data->e['id'].'.pdf';

		$url = '/shop/date:getSales?id='.$data->e['id'];
		if($data->eFarm->notEmpty()) {
			$url .= '&farm='.$data->eFarm['id'];
		}

		$content = \selling\PdfLib::build($url, $filename);

		throw new PdfAction($content, $filename);

	}, validate: ['acceptDownload'])
	->doDelete(fn($data) => throw new RedirectAction(\shop\ShopUi::adminUrl($data->e['farm'], $data->e['shop']).'&success=shop\\Date::deleted'));

new Page()
	->post('doUpdateCatalog', function($data) {

		$newStatus = POST('status', 'bool');

		$data->eCatalog = \shop\CatalogLib::getById(POST('catalog'));
		$data->eDate = \shop\DateLib::getById(POST('date'), \shop\Date::getSelection() + [
			'shop' => \shop\ShopElement::getSelection()
		]);

		$data->eDate['shop']
			->validateShare($data->eCatalog['farm'])
			->validateFrequency();

		// On ne vérifie l'existence du catalogue qu'en cas d'ajout à la vente
		if($newStatus) {
			$data->eCatalog->validateShop($data->eDate['shop']);
		}

		$fw = new FailWatch();

		\shop\DateLib::updateCatalog($data->eDate, $data->eCatalog, $newStatus);

		$fw->validate();

		throw new ViewAction($data);

	});

new \shop\DatePage()
	->applyElement(function($data, \shop\Date $eDate) {

		$eDate['shop'] = \shop\ShopLib::getById($eDate['shop']);

	})
	->update(function($data) {

		$data->e['ccPoint'] = \shop\PointLib::getByFarm($data->e['farm']);

		$data->e['cCatalog'] = \shop\CatalogLib::getByFarm($data->e['farm'], $data->e['type']);

		foreach($data->e['cCatalog'] as $eCatalog) {

			$eCatalog['selected'] = (
				$data->e['catalogs'] !== NULL and
				in_array($eCatalog['id'], $data->e['catalogs'])
			);

		}


		throw new \ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('shop', 'Date::updated'));

new Page()
	->remote('getSales', 'selling', function($data) {

		$data->e = \shop\DateLib::getById(GET('id'))->validate();
		$data->e['shop'] = \shop\ShopLib::getById($data->e['shop']);

		if($data->e['shop']->isPersonal()) {
			$data->eFarm = $data->e['farm'];
		} else {

			$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

			if(
				$data->eFarm->notEmpty() and
				\shop\ShareLib::match($data->e['shop'], $data->eFarm) === FALSE
			) {
				throw new NotExpectedAction('Invalid match');
			}

		}

		$data->cSale = \selling\SaleLib::getForLabelsByDate($data->eFarm, $data->e, selectItems: TRUE, selectPoint: TRUE);
		$data->cItem = \selling\ItemLib::getSummaryByDate($data->eFarm, $data->e);

		\selling\ItemLib::fillSummaryDistribution($data->cSale, $data->cItem);

		throw new ViewAction($data);

	});
