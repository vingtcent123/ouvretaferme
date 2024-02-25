<?php
/**
 * Close expired user accounts
 *
 */
(new Page())
	->cron('index', function($data) {

		user\DropLib::closeExpired();

	}, interval: '0 6 * * *');
?>
