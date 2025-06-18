<?php
(new Page())
	->cron('index', function($data) {

		media\CleanLib::check();
		media\CleanLib::delete();

	}, interval: '0 2 * * *');
?>