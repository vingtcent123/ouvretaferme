<?php
(new Page())
	->cron('index', function($data) {

		\mail\MailLib::sendWaiting();

	}, interval: 'permanent@2');
?>
