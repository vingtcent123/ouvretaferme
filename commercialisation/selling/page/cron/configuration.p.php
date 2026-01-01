<?php
new Page()
	->cron('newYear', function($data) {

		\farm\ConfigurationLib::newYear();

	}, interval: '0 0 1 1 *');
?>
