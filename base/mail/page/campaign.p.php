<?php
new \mail\CampaignPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$eCampaign = new \mail\Campaign([
			'farm' => $data->eFarm,
			'source' => \mail\Campaign::INPUT('source', 'source', fn($value) => $value !== NULL ? throw new NotExpectedAction() : NULL),
			'sourceGroup' => new \selling\CustomerGroup(),
			'sourceShop' => new \shop\Shop(),
			'sourcePeriod' => NULL
		]);

		switch($eCampaign['source']) {

			case \mail\Campaign::SHOP :
				$eCampaign['sourceShop'] = \shop\ShopLib::getById(INPUT('sourceShop'))->validateShare($data->eFarm);
				break;

			case \mail\Campaign::GROUP :
				$eCampaign['sourceGroup'] = \selling\CustomerGroupLib::getById(INPUT('sourceGroup'))->validateProperty('farm', $data->eFarm);
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

		$data->e['alreadyScheduled'] = \mail\CampaignLib::countScheduled($data->eFarm, $data->e['scheduledAt']);
		$data->e['limit'] = $data->eFarm->getCampaignLimit();

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCommunicationsCampaign($data->eFarm).'?success=mail:Campaign::created');
	}, onKo: fn() => \mail\Campaign::fail('createError'));

new \farm\FarmPage()
	->read('createSelect', function($data) {

		$data->cCustomerGroup = \selling\CustomerGroupLib::getByFarm($data->e);
		$data->ccShop = \shop\ShopLib::getList($data->e);

		throw new ViewAction($data);

	}, validate: ['canCommunication'])
	->read('getLimits', function($data) {

		$data->date = \mail\Campaign::POST('date', 'scheduledAt', fn() => throw new NotExpectedAction());
		$data->eCampaign = POST('campaign', 'mail\Campaign');

		$data->e['alreadyScheduled'] = \mail\CampaignLib::countScheduled($data->e, $data->date, $data->eCampaign);
		$data->e['limit'] = $data->e->getCampaignLimit();

		throw new ViewAction($data);

	}, method: 'post', validate: ['canCommunication']);

new \mail\CampaignPage()
	->read('getEmailFields', function($data) {

		$data->e['cCampaignLast'] = \mail\CampaignLib::getLastByFarm($data->e['farm'], $data->e['source']);

		throw new ViewAction($data);

	})
	->read('get', function($data) {

		$data->cEmail = $data->e['status'] === \mail\Campaign::SENT ?
			\mail\EmailLib::getByCampaign($data->e) :
			new Collection();

		$data->eFarm = $data->e['farm'];

		throw new ViewAction($data);

	})
	->write('doTest', function($data) {

		\mail\CampaignLib::sendOne($data->e['farm'], $data->e, $data->e['farm']['legalEmail'], test: TRUE);

		throw new ReloadAction('mail', 'Campaign::test');

	})
	->doDelete(fn() => throw new ReloadAction(), validate: ['canDelete', 'acceptDelete'])
	->update(function($data) {

		$data->e['cContact'] = \mail\ContactLib::getByEmails($data->e['farm'], $data->e['to'], withCustomer: TRUE);

		$data->e['alreadyScheduled'] = \mail\CampaignLib::countScheduled($data->e['farm'], $data->e['scheduledAt']) - $data->e['scheduled'];
		$data->e['limit'] = $data->e['farm']->getCampaignLimit();

		throw new ViewAction($data);

	}, validate: ['canUpdate', 'acceptUpdate'])
	->doUpdate(fn() => throw new ReloadAction('mail', 'Campaign::updated'), validate: ['canUpdate', 'acceptUpdate']);
?>
