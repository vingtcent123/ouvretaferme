<?php
(new Page())
	->cron('index', function($data) {

		\dev\ErrorPhpLib::clean();

	}, interval: '47 5 * * *');
?>
