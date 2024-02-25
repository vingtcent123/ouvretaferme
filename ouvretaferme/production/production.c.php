<?php
Privilege::register('production', [
	'admin' => FALSE,
]);

Setting::register('production', [

	'minWeekN-1' => 52-13,
	'maxWeekN+1' => 26,

	'maxSeasonStop' => 100

]);
?>
