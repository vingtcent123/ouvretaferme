<?php
new Page()
	->cron('clean', function($data) {

		\website\ContactLib::clean();

	}, interval: '0 0 1 * *');
?>
