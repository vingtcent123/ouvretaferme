<?php
new Page()
	->cron('endEmail', function($data) {

		\shop\DateLib::sendEndEmail();

	}, interval: '15 * * * *');
?>