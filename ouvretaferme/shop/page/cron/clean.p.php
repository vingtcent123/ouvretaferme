<?php

new Page()
	->cron('basket', function($data) {

		\shop\BasketLib::clean();

	}, interval: '15 * * * *');
?>
