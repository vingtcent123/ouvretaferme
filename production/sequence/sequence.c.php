<?php
Privilege::register('sequence', [
	'admin' => FALSE,
]);

Setting::register('sequence', [

	'minWeekN-1' => 26,
	'maxWeekN+1' => 26,

	'maxSeasonStop' => 100

]);
?>
