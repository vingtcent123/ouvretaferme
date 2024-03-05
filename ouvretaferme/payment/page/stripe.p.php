<?php
(new Page(function($data) {

	if(OTF_DEMO) {
		throw new \FailAction('payment\Stripe::demo.write');
	}

	$farm = GET('farm', '?int');

	$data->eFarm = \farm\FarmLib::getById($farm)->validate('canManage');

	\farm\FarmerLib::register($data->eFarm);

	}))
	->get('manage', function($data) {

		$data->eStripeFarm = \payment\StripeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	});

(new \payment\StripeFarmPage())
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

(new Page())
	->post('webhook', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate('active');
		$eStripeFarm = \payment\StripeLib::getByFarm($eFarm)->validate();

		$event = \payment\StripeLib::getEvent($eStripeFarm);
		\payment\StripeLib::webhook($eStripeFarm, $event);

		throw new VoidAction();

	});
?>