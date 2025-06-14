<?php
Database::addServer([
	'name' => 'ouvretaferme',
	'type' => 'MySQL',
	'host' => 'mysql-otf',
	'port' => 3306,
	'login' => 'root',
	'password' => '',
	'bases' => ['dev_ouvretaferme', 'demo_ouvretaferme']
]);

Setting::set('selling\remoteKey', '[KEY]');

RedisCache::addServer('default', 'redis-otf', 6379, ['timeout' => 2]);

Setting::register('mail', [

	'devSendOnly' => ['[EMAIL]'],

]);

Setting::register('map', [
	'mapboxToken' => '[TOKEN]',
]);
?>