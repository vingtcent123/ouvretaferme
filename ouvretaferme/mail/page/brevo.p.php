<?php
new Page()
	->post('webhook', function($data) {

		$payload = \mail\BrevoLib::getPayload();
		\mail\BrevoLib::webhook($payload);

		throw new VoidAction();

	});
