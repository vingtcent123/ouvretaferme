<?php
new \farm\FarmPage()
	->read('/ferme/{id}/boutique/{shop}', function($data) {

		// Liste des boutiques
		$data->ccShop = \shop\ShopLib::getList($data->e, withDeleted: TRUE);

		$eShop = GET('shop', 'shop\Shop');
		$data->eShop = $data->ccShop['selling'][$eShop['id']] ?? $data->ccShop['admin'][$eShop['id']] ?? throw new NotExistsAction();

		\farm\FarmerLib::setView('viewShopCurrent', $data->e, $data->eShop);

		$data->eShop['cGroupLimit'] = \selling\CustomerGroupLib::getByIds($data->eShop['limitGroups'], sort: ['name' => SORT_ASC]);
		$data->eShop['cCustomerLimit'] = \selling\CustomerLib::getByIds($data->eShop['limitCustomers'], sort: ['lastName' => SORT_ASC, 'firstName' => SORT_ASC]);

		if($data->eShop['shared']) {

			$data->eShop['cShare'] = \shop\ShareLib::getByShop($data->eShop);
			$data->eShop['cDepartment'] = \shop\DepartmentLib::getByShop($data->eShop);
			$data->eShop['ccRange'] = \shop\RangeLib::getByShop($data->eShop);

		}

		$data->eShop['ccPoint'] = \shop\PointLib::getByFarm($data->e);

		$data->eFarm = $data->e;

		switch($data->eShop['opening']) {

			case \shop\Shop::ALWAYS :

				$data->eFarm['cPaymentMethod'] = \payment\MethodLib::getByFarm($data->eFarm, NULL);

				$data->eShop['eDate'] = \shop\DateLib::getAlwaysByShop($data->eShop);
				$data->eShop['hasDate'] = $data->eShop['eDate']->notEmpty();

				if($data->eShop['eDate']->notEmpty()) {
					\shop\DateLib::applyManagement($data->e, $data->eShop, $data->eShop['eDate'], GET('page', 'int'));
				}

				break;

			case \shop\Shop::FREQUENCY :

				$data->eShop['cDate'] = \shop\DateLib::getByShop($data->eShop);
				$data->eShop['hasDate'] = $data->eShop['cDate']->notEmpty();

				break;

		}

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

			throw new RedirectAction(\shop\ShopUi::adminUrl($data->eFarm, $data->eShop).'?success=shop\\Shop::joined');

		} else {
			throw new ViewAction($data);
		}


	});

new shop\ShopPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validateVerified();

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
		throw new RedirectAction(\farm\FarmUi::urlShopList($data->e['farm']).'?success=shop\\Shop::deleted');
	});
?>
