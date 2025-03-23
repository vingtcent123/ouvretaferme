<?php

new Page()
	->cron('index', function($data) {

		\farm\InviteLib::deleteExpired();

	}, interval: '5 19 * * *');
?>
