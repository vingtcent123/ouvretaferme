<?php
(new Page())
	->cron('rewrite', function($data) {

		\website\DomainLib::buildRewrites();

	}, interval: '* * * * *')
	->cron('ping', function($data) {

		\website\DomainLib::buildPingUnsecure();
		\website\DomainLib::buildPingSecure();

	}, interval: '* * * * *')
	->cron('certificate', function($data) {

		\website\DomainLib::buildCertificate();

	}, interval: '3 */12 * * *')
	->cron('clean', function($data) {

		\website\DomainLib::cleanRewrites();

	}, interval: '28 4 * * *');
?>