<?php
/**
 * Close expired user accounts
 *
 */
new Page()
	->cron('index', function($data) {

		\farm\FarmLib::createNextSeason();

	}, interval: '0 0 1 10 *');
?>