<?php
Privilege::register('company', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('company', [
	'mindeeApiKey' => '',
]);
?>
