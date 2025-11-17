<?php
new \farm\FarmPage()
	->read('/ferme/{id}/date/{date}', function($data) {

		$data->eDate = \shop\DateLib::getById(GET('date'))->validate();

		$data->eShop = \shop\ShopLib::getById($data->eDate['shop'])->validateShareRead($data->e);
		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->eShop['farm']);

		if($data->eShop['shared']) {

			$data->eShop['cShare'] = \shop\ShareLib::getByShop($data->eShop);
			$data->eShop['ccRange'] = \shop\RangeLib::getByShop($data->eShop);
			$data->eShop['cDepartment'] = \shop\DepartmentLib::getByShop($data->eShop);

			if(get_exists('farm')) {
				$eShareSelected = $data->eShop['cShare'][GET('farm', 'int')] ?? new \shop\Share();
			} else {
				$eShareSelected = $data->eShop['cShare'][$data->e['id']] ?? new \shop\Share();
			}

			$data->eShop['eFarmSelected'] = $eShareSelected->empty() ? new \farm\Farm() : $eShareSelected['farm'];

		} else {
			$data->eShop['eFarmSelected'] = new \farm\Farm();
		}

		\shop\DateLib::applySales($data->eDate);

		$data->cSale = \selling\SaleLib::getByDate($data->eDate, eFarm: $data->eShop['eFarmSelected'], select: \selling\Sale::getSelection() + [
			'shopPoint' => \shop\PointElement::getSelection()
		]);

		$data->eDate['farm'] = $data->eShop['farm'];
		$data->eDate['shop'] = $data->eShop;

		$data->eDate['cCatalog'] = $data->eDate['catalogs'] ?
			\shop\CatalogLib::getByIds($data->eDate['catalogs']) :
			new Collection();

		$cProduct = \shop\ProductLib::getByDate($data->eDate, reorderChildren: TRUE);
		$data->eDate['cCustomer'] = \selling\CustomerLib::getLimitedByProducts($cProduct);
		$data->eDate['cGroup'] = \selling\CustomerGroupLib::getLimitedByProducts($cProduct);

		// Uniquement les boutiques avec un seul producteur
		$cProduct->mergeCollection(\shop\ProductLib::aggregateBySales($data->cSale, $cProduct));
		$cProduct->sort(['product' => ['name']], natural: TRUE);

		if($data->eShop['shared']) {

			$data->eDate['cFarm'] = $data->eShop['cShare']->getColumnCollection('farm', index: 'farm');

			if($data->eShop['eFarmSelected']->notEmpty()) {
				$cProduct->filter(fn($eProduct) => $eProduct['farm']['id'] === $data->eShop['eFarmSelected']['id']);
			}

		} else {

			$data->eDate['cFarm'] = $data->eDate['catalogs'] ?
				\farm\FarmLib::getByIds($data->eDate['cCatalog']->getColumnCollection('farm'), index: 'id') :
				new Collection([
					$data->e['id'] => $data->e
				]);

		}

		if($data->eDate->isPast()) {
			$cProduct->filter(fn($eProduct) => $eProduct['sold'] > 0);
		}

		\shop\ProductLib::applyIndexing($data->eShop, $data->eDate, $cProduct);
		$data->eDate['nProduct'] = $cProduct->count();

		$data->eDate['cCategory'] = \selling\CategoryLib::getByFarm($data->e);
		$data->eDate['ccPoint'] = \shop\PointLib::getByDate($data->eDate);

		$data->eFarm = $data->e;

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);

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

			$data->eDateBase->validateProperty('shop', $data->eShop);

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

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canSelling');
		$data->eShop = \shop\ShopLib::getById($data->e['shop'])->validateShareRead($data->eFarm);

		$data->cSale = \selling\SaleLib::getForLabelsByDate($data->eFarm, $data->e);

		if($data->cSale->empty()) {
			throw new NotExpectedAction('No sale');
		}

		$filename = 'sales-'.$data->e['id'].'.pdf';
		$content = \selling\PdfLib::build('/shop/date:getSales?id='.$data->e['id'].'&farm='.$data->eFarm['id'], $filename);

		throw new PdfAction($content, $filename);

	}, validate: [])
	->doDelete(fn($data) => throw new RedirectAction(\shop\ShopUi::adminUrl($data->e['farm'], $data->e['shop']).'&success=shop:Date::deleted'));

new Page()
	->post('doUpdateCatalog', function($data) {

		$newStatus = POST('status', 'bool');

		$data->eCatalog = \shop\CatalogLib::getById(POST('catalog'));
		$data->eDate = \shop\DateLib::getById(POST('date'), \shop\Date::getSelection() + [
			'shop' => \shop\ShopElement::getSelection()
		]);

		$data->eDate['shop']->validateShareRead($data->eCatalog['farm']);

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

			if(\shop\ShareLib::match($data->e['shop'], $data->eFarm) === FALSE) {
				throw new NotExpectedAction('Invalid match');
			}

		}

		$data->cSale = \selling\SaleLib::getForLabelsByDate($data->eFarm, $data->e, selectItems: TRUE, selectPoint: TRUE);
		$data->cItem = \selling\ItemLib::getSummaryByDate($data->eFarm, $data->e);

		throw new ViewAction($data);

	});
