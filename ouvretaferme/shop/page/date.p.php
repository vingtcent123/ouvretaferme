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

		$data->cProduct = \selling\ProductLib::getForDate($data->e);

		// Si c'est une copie : récupérer également la liste des produits de la date en question
		$data->eDateBase = \shop\DateLib::getById(GET('date'));

		if($data->eDateBase->notEmpty()) {
			$data->eDateBase->validate('canRead');
			$data->eDateBase['cProduct'] = \shop\ProductLib::copyByDate($data->eShop, $data->eDateBase);
		}

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

		\farm\FarmerLib::register($data->eFarm);

		$data->e['farm'] = $data->eFarm;

		if($data->e->notEmpty()) {
			$data->e['cProduct'] = \shop\ProductLib::getByDate($data->e, onlyActive: FALSE);
		}

		$data->cSale = \selling\SaleLib::getByDate($data->e, NULL, select: \selling\Sale::getSelection() + [
			'shopPoint' => \shop\PointElement::getSelection()
		], sort: 'shopPoint');

		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->eFarm);
		$data->e['ccPoint'] = \shop\PointLib::getByDate($data->e);

		throw new \ViewAction($data);

	})
	->write('doCreateProducts', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canManage');
		$eShop = \shop\ShopLib::getById($data->e['shop']['id'] ?? NULL)->validate('canWrite');

		if($data->e['shop']['id'] !== $eShop['id']) {
			throw new NotExpectedAction();
		}

		$fw = new FailWatch();

		$products = POST('products', 'array', []);

		$cProductSelling = \selling\ProductLib::getForDate($data->e);
		$data->cProduct = \shop\ProductLib::prepareSeveral($data->e, $cProductSelling, $products, $_POST);

		$fw->validate();

		\shop\ProductLib::addSeveral($data->cProduct);


		throw new ReloadAction('shop', 'Products::created');

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
		$data->cSale->sort('shopPoint');

		$data->cItem = \selling\ItemLib::getSummaryByDate($data->e);

		throw new ViewAction($data);

	});