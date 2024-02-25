<?php
(new Page())
	->cron('index', function($data) {

		\dev\MinifyLib::clean();

	}, interval: '17 7 * * *');
?>
