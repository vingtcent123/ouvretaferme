<?php
Package::setList([
	'main' => 'ouvretaferme',
	'core' => 'framework',
	'dev' => 'framework',
	'editor' => 'framework',
	'example' => 'framework',
	'language' => 'framework',
	'session' => 'framework',
	'storage' => 'framework',
	'user' => 'framework',
	'util' => 'framework',
	'analyze' => 'ouvretaferme',
	'farm' => 'ouvretaferme',
	'gallery' => 'ouvretaferme',
	'hr' => 'ouvretaferme',
	'mail' => 'ouvretaferme',
	'map' => 'ouvretaferme',
	'media' => 'ouvretaferme',
	'payment' => 'ouvretaferme',
	'plant' => 'ouvretaferme',
	'production' => 'ouvretaferme',
	'selling' => 'ouvretaferme',
	'series' => 'ouvretaferme',
	'shop' => 'ouvretaferme',
	'website' => 'ouvretaferme',
]);

Package::setObservers([
	'lib' => [
		'user' => [
			'sendVerifyEmail' => ['main'],
			'signUpCreate' => ['main', 'selling'],
			'close' => ['main'],
			'logIn' => ['session', 'farm'],
			'logOut' => ['session'],
			'formLog' => ['farm'],
			'formSignUp' => ['farm'],
			'update' => ['selling'],
		],
		'lime' => [
			'loadConf' => ['media'],
		],
		'shop' => [
			'saleConfirmed' => ['shop'],
			'saleUpdated' => ['shop'],
			'salePaid' => ['shop'],
			'saleFailed' => ['shop'],
			'saleCanceled' => ['shop'],
		],
	],
	'ui' => [
		'user' => [
			'emailSignUp' => ['main'],
		],
	],
]);
?>