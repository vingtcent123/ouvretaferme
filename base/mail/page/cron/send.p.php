<?php
new Page()
	->cron('index', function($data) {

		\mail\SendLib::sendWaiting();
		\mail\CampaignLib::sendConfirmed();

	}, interval: 'permanent@2');
?>
