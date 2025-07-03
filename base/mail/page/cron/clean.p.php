<?php

new Page()
	->cron('index', function($data) {

		\mail\SendLib::clean();

	}, interval: '0 12 * * *');
?>
