<?php
new \mail\CampaignPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$eCampaign = new \mail\Campaign([
			'farm' => $data->eFarm,
			'source' => \mail\Campaign::GET('source', 'source'),
			'sourceGroup' => new \selling\Group(),
			'sourceShop' => new \shop\Shop(),
			'sourcePeriod' => NULL
		]);

		switch($eCampaign['source']) {

			case \mail\Campaign::SHOP :
				$eCampaign['sourceShop'] = \shop\ShopLib::getById(INPUT('shop'))->validateShareRead($data->eFarm);
				break;

			case \mail\Campaign::GROUP :
				$eCampaign['sourceGroup'] = \selling\GroupLib::getById(INPUT('group'))->validateProperty('farm', $data->eFarm);
				break;

			case \mail\Campaign::PERIOD :
				$eCampaign['sourcePeriod'] = \mail\Campaign::GET('period', 'sourcePeriod', fn() => throw new NotExpectedAction('Invalid value'));
				break;

		}

		return $eCampaign;

	})
	->create(function($data) {

		$data->e['cContact'] = \mail\ContactLib::getByCampaign($data->e, withCustomer: TRUE);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCommunicationsMailing($data->eFarm).'?success=mail:Campaign::created');
	});

new \farm\FarmPage()
	->read('createSelect', function($data) {

		$data->cGroup = \selling\GroupLib::getByFarm($data->e);
		$data->ccShop = \shop\ShopLib::getList($data->e);

		throw new ViewAction($data);

	}, validate: ['canCommunication']);

new \mail\CampaignPage()
	->doDelete(fn() => throw new ReloadAction());
?>
