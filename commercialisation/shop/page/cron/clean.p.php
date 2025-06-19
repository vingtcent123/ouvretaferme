<?php

new Page()
	->cron('expired', function($data) {

		\shop\SaleLib::cancelExpired();

	}, interval: '15 * * * *');
?>
