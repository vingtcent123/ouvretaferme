<?php
/**
 * Delete expired subscriptions
 *
 */
new Page()
	->cron('index', function($data) {

		\company\SubscriptionLib::deleteExpiredSubscriptions();

	}, interval: '0 6 * * *');
?>
