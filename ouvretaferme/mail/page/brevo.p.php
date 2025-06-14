<?php
new Page()
	->post('webhook', function($data) {

		file_put_contents('/tmp/webhook'.time(), var_export($_POST, true));

		throw new VoidAction();

	});
?>