<?php
/**
 * Expires memberships
 *
 */
new Page()
	->cron('index', function($data) {

		\association\MembershipLib::expires();

	}, interval: '0 1 1 1 *');
?>
