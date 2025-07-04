<?php
new \mail\EmailPage()
	->read('index', function($data) {
		throw new ViewAction($data);
	});
?>
