<?php
/**
 * Update seniority
 *
 */
(new Page())
	->cron('index', function($data) {

		\hr\WorkingTimeLib::calculateMissing();

	}, interval: '0 3 * * *');
?>
