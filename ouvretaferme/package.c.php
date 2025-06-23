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
	'account' => 'accounting',
	'asset' => 'accounting',
	'bank' => 'accounting',
	'company' => 'accounting',
	'journal' => 'accounting',
	'overview' => 'accounting',
	'pdf' => 'accounting',
]);

Package::setObservers([
	'lib' => [
		'user' => [
			'canUpdate' => ['main'],
			'sendVerifyEmail' => ['main'],
			'signUpCreate' => ['main', 'selling'],
			'close' => ['main'],
			'logIn' => ['session', 'farm', 'company'],
			'logOut' => ['session'],
			'update' => ['selling'],
			'formLog' => ['farm', 'company'],
			'formSignUp' => ['farm', 'company'],
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