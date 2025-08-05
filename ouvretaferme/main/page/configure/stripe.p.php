<?php
// Lancer le script php framework/lime.php -a ouvretaferme -e prod configure/stripe farm=42 paymentIntent=pi_xyz
new Page()
	->cli('index', function($data) {


		$eStripeFarm = \payment\StripeLib::getByFarm(new \farm\Farm(['id' => GET('farm', 'int')]));
		$paymentDetails = \payment\StripeLib::getPaymentIntentDetails($eStripeFarm, GET('paymentIntent'));

		dd($paymentDetails);

		//dd(ip2long('91.173.108.131'));

	});
