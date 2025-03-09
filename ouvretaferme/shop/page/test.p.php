<?php
new Page()
	->get('index', function($data) {
		throw new ViewAction($data);
	});
?>
