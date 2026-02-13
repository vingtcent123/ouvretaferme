<?php
/**
 * Regerates demo
 *
 */
new Page()
	->cron('index', function($data) {

		\farm\DemoLib::rebuild();

	}, interval: '0 4 * * *');
?>
