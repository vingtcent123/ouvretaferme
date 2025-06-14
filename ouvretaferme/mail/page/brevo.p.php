<?php
new Page()
	->post('webhook', function($data) {

		$payload = \mail\BrevoLib::getPayload();
		\payment\StripeLib::webhook($payload);

		throw new VoidAction();

	});
?>