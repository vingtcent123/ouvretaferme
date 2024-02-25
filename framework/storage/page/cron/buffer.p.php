<?php
/**
 * Optimize new images
 *
 */
(new Page())
	->cron('index', function($data) {

		\storage\BufferLib::run();

	}, interval: 'permanent@2');

?>
