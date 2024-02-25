<?php
Privilege::register('farm', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('farm', [
	'seasonBegin' => '01-01',
	'categoriesLimit' => 5,
	'newSeason' => 10
]);
?>