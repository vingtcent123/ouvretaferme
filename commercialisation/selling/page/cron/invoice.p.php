<?php
new Page()
	->cron('index', function($data) {

		\selling\InvoiceLib::generateWaiting();
		\selling\InvoiceLib::generateFail();

	}, interval: 'permanent@2');
?>
