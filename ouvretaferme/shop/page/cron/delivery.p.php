<?php
new Page()
	->cron('email', function($data) {

		\shop\DateLib::sendEndEmail();

	}, interval: '15 * * * *')
	->cron('finish', function($data) {

		\shop\DateLib::finish();

	}, interval: 'permanent@2');
?>