<?php
new Page()
	->cron('index', function($data) {

		\journal\OperationLib::cleanInvoices();

	}, interval: '15 2 * * *');
?>
