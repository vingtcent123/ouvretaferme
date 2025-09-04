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

RedisCache::addServer('default', 'redis-otf', 6379, ['timeout' => 2]);

require_once Lime::getPath().'/package.c.php';

\map\MapSetting::$mapboxToken = '[TOKEN]';

\selling\SellingSetting::$remoteKey = '[KEY]';

\mail\MailSetting::$devSendOnly = ['[EMAIL]'];
?>
