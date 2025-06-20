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
	'selling' => 'commercialisation',
	'website' => 'ouvretaferme',
	'farm' => 'base',
	'hr' => 'base',
	'mail' => 'base',
	'media' => 'base',
	'payment' => 'commercialisation',
	'shop' => 'commercialisation',
	'gallery' => 'production',
	'map' => 'production',
	'plant' => 'production',
	'sequence' => 'production',
	'series' => 'production',
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
			'update' => ['selling'],
			'formLog' => ['farm'],
			'formSignUp' => ['farm'],
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