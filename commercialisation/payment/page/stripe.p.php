<?php
(new Page(function($data) {

	if(OTF_DEMO) {
		throw new \FailAction('payment\Stripe::demo.write');
	}

	$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');


	}))
	->get('manage', function($data) {

		$data->eStripeFarm = \payment\StripeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	});

new \payment\StripeFarmPage()
	->getCreateElement(function($data) {

		return new \payment\StripeFarm([
			'farm' => \farm\FarmLib::getById(INPUT('farm')),
		]);

	})
	->doCreate(function($data) {
		throw new ReloadAction('payment', 'StripeFarm::created');
	})
	->doDelete(function($data) {
		throw new ReloadAction('payment', 'StripeFarm::deleted');
	});

new Page()
	->post('webhook', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate('active');
		$eStripeFarm = \payment\StripeLib::getByFarm($eFarm)->validate();

		$event = \payment\StripeLib::getEvent($eStripeFarm);
		\payment\StripeLib::webhook($eStripeFarm, $event);

		throw new VoidAction();

	})
	->cli('getWebhooks', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate('active');
		$eStripeFarm = \payment\StripeLib::getByFarm($eFarm)->validate();

		var_dump(\payment\StripeLib::getWebhooks($eStripeFarm));

		throw new VoidAction();

	})
	->cli('createWebhook', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate('active');
		$eStripeFarm = \payment\StripeLib::getByFarm($eFarm)->validate();

		var_dump(\payment\StripeLib::createWebhook($eStripeFarm));

		throw new VoidAction();

	});
?>