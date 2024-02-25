<?php
Privilege::register('map', [
	'admin' => FALSE,
]);

Setting::register('map', [

	'mapboxToken' => fn() => throw new Exception('Missing mapbox token'),

]);
?>
