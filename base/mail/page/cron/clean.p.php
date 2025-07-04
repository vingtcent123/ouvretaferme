<?php

new Page()
	->cron('index', function($data) {

		\mail\EmailLib::clean();

	}, interval: '0 12 * * *');
?>
