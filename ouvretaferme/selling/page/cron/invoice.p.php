<?php
(new Page())
	->cron('index', function($data) {

		\selling\InvoiceLib::generateWaiting();

	}, interval: 'permanent@2');
?>
