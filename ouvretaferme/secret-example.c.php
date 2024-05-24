<?php
Database::addServer([
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

	'smtpServers' => [
		'user' => [
			'host' => '[HOST]',
			'port' => 465,
			'from' => '[FROM]',
			'user' => '[USER]',
			'password' => '[PASSWORD]',
		],
		'shop' =>[
			'host' => '[HOST]',
			'port' => 465,
			'from' => '[FROM]',
			'user' => '[USER]',
			'password' => '[PASSWORD]',
		],
		'document' =>[
			'host' => '[HOST]',
			'port' => 465,
			'from' => '[FROM]',
			'user' => '[USER]',
			'password' => '[PASSWORD]',
		]
	],

	'devSendOnly' => ['[EMAIL]'],

]);

Setting::register('map', [
	'mapboxToken' => '[TOKEN]',
]);
?>