<?php
/**
 * Generate missing documents and send by email
 *
 */
new Page()
	->cron('index', function($data) {

		\association\MembershipLib::generateDocuments();
		\association\MembershipLib::sendDocuments();

	}, interval: 'permanent@2');
?>
