<?php
// Lancer le script php framework/lime.php -a ouvretaferme -e prod configure/stripe
new Page()
	->cli('index', function($data) {


		$eStripeFarm = \payment\StripeLib::getByFarm(new \farm\Farm(['id' => 527]));
		$paymentDetails = \payment\StripeLib::getPaymentIntentDetails($eStripeFarm, 'pi_3Rhadd2McNZtv14d1ZTXKrUa');

		dd($paymentDetails);

		//dd(ip2long('91.173.108.131'));

	});
