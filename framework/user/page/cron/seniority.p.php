<?php
/**
 * Update seniority
 *
 */
(new Page())
	->cron('index', function($data) {

		user\UserLib::updateSeniority();

	}, interval: '0 0 * * *');
?>
