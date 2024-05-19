<?php
(new \shop\DatePage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$data->eShop = \shop\ShopLib::getById(INPUT('shop'))->validateProperty('farm', $data->eFarm);

		return new \shop\Date([
			'farm' => $data->eFarm,
			'shop' => $data->eShop
		]);

	})
	->create(function($data) {

		$data->cProduct = \selling\ProductLib::getForShop($data->eFarm);

		// Si c'est une copie : récupérer également la liste des produits de la date en question
		$data->eDateBase = \shop\DateLib::getById(GET('date'));

		if($data->eDateBase->notEmpty()) {
			$data->eDateBase->validate('canRead');
			$data->eDateBase['cProduct'] = \shop\ProductLib::getByDate($data->eDateBase, onlyActive: FALSE);
		}

		$data->e['ccPoint'] = \shop\PointLib::getByShop($data->e['shop']);

		throw new \ViewAction($data);

	})
	->doCreate(fn($data) => throw new RedirectAction(\shop\ShopUi::adminDateUrl($data->e['farm'], $data->e['shop'], $data->e).'?success=shop:'.(GET('copied', 'bool') ? 'Date::created' : 'Date::copied')))
	->update(function($data) {

		$data->e['ccPoint'] = \shop\PointLib::getByShop($data->e['shop']);

		throw new \ViewAction($data);

	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->doUpdate(fn() => throw new ReloadAction('shop', 'Date::updated'))
	->read('/ferme/{farm}/boutique/{shop}/date/{id}', function($data) {

		$data->eShop = \shop\ShopLib::getById(GET('shop'))->validate('canRead');

		if($data->e['shop']['id'] !== $data->eShop['id']) {
			throw new NotExpectedAction('Inconsistency');
		}

		$data->eShop['ccPoint'] = \shop\PointLib::getByShop($data->eShop);

		\shop\DateLib::applySales($data->e);

		$data->eFarm = $data->eShop['farm'];
		\farm\FarmerLib::register($data->eFarm);

		if($data->e->notEmpty()) {
			$data->e['cProduct'] = \shop\ProductLib::getByDate($data->e, onlyActive: FALSE);
		}

		$data->cSale = \selling\SaleLib::getByDate($data->e, NULL, select: \selling\Sale::getSelection() + [
			'shopPoint' => \shop\PointElement::getSelection()
		]);

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

		$cProductSelling = \selling\ProductLib::getForShop($data->eFarm);
		$data->cProduct = \shop\ProductLib::prepareSeveral($data->e, $cProductSelling, $products, $_POST);

		$fw->validate();

		\shop\ProductLib::addSeveral($data->cProduct);


		throw new ReloadAction('shop', 'Products::created');

	})
	->write('doUpdatePoint', function($data) {

		$data->ePoint = \shop\PointLib::getById(POST('point'))
			->validate('isActive')
			->validateProperty('shop', $data->e['shop']);

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

(new Page())
	->get('getSales', function($data) {

		$data->e = \shop\DateLib::getById(GET('id'))->validate('canRemote');
		$data->e['shop'] = \shop\ShopLib::getById($data->e['shop']);

		$data->cSale = \selling\SaleLib::getForLabelsByDate($data->e, selectItems: TRUE, selectPoint: TRUE);
		$data->cItem = \selling\ItemLib::getSummaryByDate($data->e);

		throw new ViewAction($data);

	});