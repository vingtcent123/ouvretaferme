<?php
new Page()
	->cron('auto', function($data) {

		\selling\SaleLib::autoClosing();

	}, interval: '0 0 * * *');
?>
