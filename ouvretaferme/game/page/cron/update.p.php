<?php
new Page()
	->cron('update', function($data) {

		\game\PlayerLib::resetTime();

	}, interval: '0 0 * * *');
?>