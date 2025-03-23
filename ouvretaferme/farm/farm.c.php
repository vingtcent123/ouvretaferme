<?php
Privilege::register('farm', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('farm', [
	'seasonBegin' => '01-01',
	'inviteDelay' => 7,
	'categoriesLimit' => 5,
	'newSeason' => 10,
	'calendarLimit' => 20
]);
?>