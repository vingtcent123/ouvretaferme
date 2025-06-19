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
	'gallery' => 'ouvretaferme',
	'map' => 'ouvretaferme',
	'plant' => 'ouvretaferme',
	'sequence' => 'ouvretaferme',
	'series' => 'ouvretaferme',
	'website' => 'ouvretaferme',
	'farm' => 'base',
	'hr' => 'base',
	'mail' => 'base',
	'media' => 'base',
	'payment' => 'commercialisation',
	'selling' => 'commercialisation',
	'shop' => 'commercialisation',
]);

Package::setObservers([
	'lib' => [
		'user' => [
			'canUpdate' => ['main'],
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
			'signUpFormBottom' => ['main'],
			'emailSignUp' => ['main'],
		],
	],
]);
?>