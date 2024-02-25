<?php

(new Page())
	->cron('index', function($data) {

		\selling\PdfLib::clean();

	}, interval: '19 5 * * *');
?>
