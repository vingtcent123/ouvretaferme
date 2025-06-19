<?php
new Page()
	->cron('email', function($data) {

		\shop\DateLib::end();

	}, interval: '15 * * * *')
	->cron('finish', function($data) {

		\shop\DateLib::finish();

	}, interval: 'permanent@2');
?>