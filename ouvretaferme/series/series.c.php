<?php
Privilege::register('series', [

	'admin' => FALSE,

]);

Setting::register('series', [

	'missingWeeks' => 8,

	'duplicateLimit' => 10,
	'duplicateInterval' => ['min' => -52, 'max' => 52],

]);
?>
