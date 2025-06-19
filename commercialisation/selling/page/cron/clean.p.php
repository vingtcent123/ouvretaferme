<?php

new Page()
	->cron('pdf', function($data) {

		\selling\PdfLib::clean();

	}, interval: '19 5 * * *');
?>
