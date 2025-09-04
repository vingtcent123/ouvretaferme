<?php
namespace map;

class MapSetting extends \Settings {

	public static $mapboxToken = NULL;

}

MapSetting::$mapboxToken = fn() => throw new \Exception('Missing mapbox token');
?>
