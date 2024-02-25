<?php
/**
 * Clean user logs
 *
 */
(new Page())
	->cron('index', function($data) {

		user\LogLib::clean();

		user\LogLib::cleanAuto();

		user\UserLib::cleanForgottenPasswordHash();

	}, interval: '0 6 * * *');
?>
