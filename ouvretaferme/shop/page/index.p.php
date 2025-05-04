<?php
new \farm\FarmPage()
	->read('/ferme/{id}/boutique/{shop}', function($data) {

		\farm\FarmerLib::setView('viewShop', $data->e, \farm\Farmer::SHOP);

		// Liste des boutiques
		$data->ccShop = \shop\ShopLib::getList($data->e);

		$eShop = GET('shop', 'shop\Shop');
		$data->eShop = $data->ccShop['selling'][$eShop['id']] ?? $data->ccShop['admin'][$eShop['id']] ?? throw new NotExistsAction();

		\farm\FarmerLib::setView('viewShopCurrent', $data->e, $data->eShop);

		$data->eShop['cCustomer'] = \selling\CustomerLib::getByIds($data->eShop['limitCustomers'], sort: ['lastName' => SORT_ASC, 'firstName' => SORT_ASC]);

		if($data->eShop['shared']) {
			$data->eShop['cShare'] = \shop\ShareLib::getByShop($data->eShop);
			$data->eShop['cDepartment'] = \shop\DepartmentLib::getByShop($data->eShop);
			$data->eShop['ccRange'] = \shop\RangeLib::getByShop($data->eShop);
		}

		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->e);

		// Liste des dates de la boutique sélectionnée
		$data->eShop['cDate'] = \shop\DateLib::getByShop($data->eShop);

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	}, validate: ['canSelling']);

new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

	})
	->get( 'join', function($data) {
		throw new ViewAction($data);
	})
	->post( 'doJoin', function($data) {

		$data->eShop = \shop\ShopLib::getByKey(POST('key'));

		if($data->eShop->empty()) {
			throw new FailAction('shop\Shop::invalidKey');
		}

		if($data->eShop['farm']['id'] === $data->eFarm['id']) {
			throw new FailAction('shop\Shop::invalidFarm');
		}

		$data->hasJoin = \shop\ShareLib::match($data->eShop, $data->eFarm);

		if(
			$data->hasJoin === FALSE and
			post_exists('do')
		) {

			\shop\ShopLib::joinShared($data->eShop, $data->eFarm);

			throw new RedirectAction(\shop\ShopUi::adminUrl($data->eFarm, $data->eShop).'?success=shop:Shop::joined');

		} else {
			throw new ViewAction($data);
		}


	});

new shop\ShopPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$data->eFarm->validateLegalComplete();

		return new \shop\Shop([
			'farm' => $data->eFarm,
			'shared' => INPUT('shared', '?bool')
		]);

	})
	->create()
	->doCreate(function($data) {

		$url = \shop\ShopUi::adminUrl($data->eFarm, $data->e);

		if($data->e['shared']) {
			$url .= '?tab=farmers';
		}

		throw new RedirectAction($url);
	})
	->read('invite', function($data) {

		if($data->e['shared'] === FALSE) {
			throw new NotExpectedAction();
		}

		throw new \ViewAction($data);

	}, validate: ['canWrite'])
	->write('doRegenerateSharedHash', function($data) {

		\shop\ShopLib::regenerateSharedHash($data->e);

		throw new ReloadLayerAction();

	})
	->read('website', function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$data->eWebsite = \website\WebsiteLib::getByFarm($data->eFarm);
		$data->eDate = \shop\DateLib::getMostRelevantByShop($data->e, one: TRUE);

		throw new \ViewAction($data);

	}, validate: ['canWrite'])
	->update(function($data) {
		throw new ViewAction($data);
	}, page: 'updateEmbed')
	->doUpdateProperties('doUpdateEmbed', ['embedUrl', 'embedOnly'], fn() => throw new ReloadAction('shop', 'Shop::updatedEmbed'))
	->read('emails', function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);
		$data->emails = \shop\ShopLib::getEmails($data->e);

		throw new \ViewAction($data);

	}, validate: ['canWrite'])
	->doUpdateProperties('doUpdateStatus', ['status'], function($data) {
		throw new ReloadAction('shop', $data->e['status'] === \shop\Shop::OPEN ? 'Shop::opened' : 'Shop::closed');
	})
	->doUpdateProperties('doUpdatePayment', ['hasPayment'], function($data) {
		throw new ReloadAction('shop', $data->e['hasPayment'] ? 'Shop::paymentOn' : 'Shop::paymentOff');
	}, validate: ['canUpdate', 'isPersonal'])
	->doUpdateProperties('doUpdatePoint', ['hasPoint'], function($data) {
		throw new ReloadAction('shop', $data->e['hasPoint'] ? 'Shop::pointOn' : 'Shop::pointOff');
	})
	->doDelete(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlShopList($data->e['farm']).'?success=shop:Shop::deleted');
	});
?>
