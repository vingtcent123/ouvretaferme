<?php

(new Page())
	->cron('index', function($data) {

		\mail\MailLib::clean();

	}, interval: '0 12 * * *');
?>
