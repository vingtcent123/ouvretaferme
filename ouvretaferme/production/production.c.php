<?php
Privilege::register('production', [
	'admin' => FALSE,
]);

Setting::register('production', [

	'minWeekN-1' => 26,
	'maxWeekN+1' => 26,

	'maxSeasonStop' => 100

]);
?>
