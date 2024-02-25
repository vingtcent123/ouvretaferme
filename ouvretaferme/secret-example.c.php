<?php
Database::addServer([
	'type' => 'MySQL',
	'host' => '[HOST]',
	'port' => 3306,
	'login' => '[USER]',
	'password' => '[PASSWORD]',
	'bases' => ['dev_ouvretaferme', 'demo_ouvretaferme']
]);

Setting::set('selling\remoteKey', '[KEY]');

RedisCache::addServer('default', 'redis', 6379, ['timeout' => 2]);

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