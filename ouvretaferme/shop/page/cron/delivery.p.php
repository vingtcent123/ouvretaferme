<?php
new Page()
	->cron('email', function($data) {

		\shop\DateLib::sendEndEmail();

	}, interval: '15 * * * *');
?>