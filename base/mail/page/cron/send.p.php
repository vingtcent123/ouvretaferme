<?php
new Page()
	->cron('index', function($data) {

		\mail\SendLib::sendWaiting();

	}, interval: 'permanent@2');
?>
