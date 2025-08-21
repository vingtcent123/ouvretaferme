<?php
new \mail\CampaignPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$eCampaign = new \mail\Campaign([
			'farm' => $data->eFarm,
			'source' => \mail\Campaign::INPUT('source', 'source', fn($value) => $value !== NULL ? throw new NotExpectedAction() : NULL),
			'sourceGroup' => new \selling\Group(),
			'sourceShop' => new \shop\Shop(),
			'sourcePeriod' => NULL
		]);

		switch($eCampaign['source']) {

			case \mail\Campaign::SHOP :
				$eCampaign['sourceShop'] = \shop\ShopLib::getById(INPUT('sourceShop'))->validateShareRead($data->eFarm);
				break;

			case \mail\Campaign::GROUP :
				$eCampaign['sourceGroup'] = \selling\GroupLib::getById(INPUT('sourceGroup'))->validateProperty('farm', $data->eFarm);
				break;

			case \mail\Campaign::PERIOD :
				$eCampaign['sourcePeriod'] = \mail\Campaign::INPUT('sourcePeriod', 'sourcePeriod', fn() => throw new NotExpectedAction('Invalid value'));
				break;

		}

		if(get_exists('copy')) {

			$eCampaignCopy = \mail\CampaignLib::getById(GET('copy'))->validateProperty('farm', $data->eFarm);

			$eCampaign['subject'] = $eCampaignCopy['subject'];
			$eCampaign['content'] = $eCampaignCopy['content'];

		}

		return $eCampaign;

	})
	->create(function($data) {

		$data->e['cContact'] = \mail\ContactLib::getByCampaign($data->e, withCustomer: TRUE);
		$data->e['cCampaignLast'] = \mail\CampaignLib::getLastByFarm($data->eFarm, $data->e['source']);
		$data->e['scheduledAt'] = get_exists('scheduledAt') ?
			max($data->e->getMinScheduledAt(), GET('scheduledAt', default: currentDatetime())) :
			$data->e->getMinFavoriteScheduledAt($data->eFarm);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCommunicationsCampaign($data->eFarm).'?success=mail:Campaign::created');
	}, onKo: fn() => \mail\Campaign::fail('createError'));

new \farm\FarmPage()
	->read('createSelect', function($data) {

		$data->cGroup = \selling\GroupLib::getByFarm($data->e);
		$data->ccShop = \shop\ShopLib::getList($data->e);

		throw new ViewAction($data);

	}, validate: ['canCommunication']);

new \mail\CampaignPage()
	->read('getEmailFields', function($data) {

		$data->e['cCampaignLast'] = \mail\CampaignLib::getLastByFarm($data->e['farm'], $data->e['source']);

		throw new ViewAction($data);

	})
	->doDelete(fn() => throw new ReloadAction(), validate: ['canDelete', 'acceptDelete'])
	->update(function($data) {

		$data->e['cContact'] = \mail\ContactLib::getByEmails($data->e['farm'], $data->e['to'], withCustomer: TRUE);

		throw new ViewAction($data);

	}, validate: ['canUpdate', 'acceptUpdate'])
	->doUpdate(fn() => throw new ReloadAction('mail', 'Campaign::updated'), validate: ['canUpdate', 'acceptUpdate']);
?>
