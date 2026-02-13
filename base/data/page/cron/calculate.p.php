<?php
/**
 * Compute farm data
 *
 */
new Page()
	->cron('index', function($data) {

		\data\FarmLib::calculate();

	}, interval: '0 * * * *');
?>
