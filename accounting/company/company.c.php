<?php
Privilege::register('company', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('company', [
	'subscriptionPrices' => [
		\company\SubscriptionElement::ACCOUNTING => 100,
		\company\SubscriptionElement::PRODUCTION => 100,
		\company\SubscriptionElement::SALES => 200,
	],
	'subscriptionPackPrice' => 300,
]);
?>
