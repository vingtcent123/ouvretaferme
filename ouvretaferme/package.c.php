<?php
Package::setList([
	'account' => 'accounting',
	'analyze' => 'ouvretaferme',
	'asset' => 'accounting',
	'association' => 'ouvretaferme',
	'bank' => 'accounting',
	'company' => 'accounting',
	'core' => 'framework',
	'dev' => 'framework',
	'editor' => 'framework',
	'example' => 'framework',
	'farm' => 'base',
	'gallery' => 'production',
	'hr' => 'base',
	'journal' => 'accounting',
	'language' => 'framework',
	'mail' => 'base',
	'main' => 'ouvretaferme',
	'map' => 'production',
	'media' => 'base',
	'overview' => 'accounting',
	'payment' => 'commercialisation',
	'pdf' => 'accounting',
	'plant' => 'production',
	'selling' => 'commercialisation',
	'sequence' => 'production',
	'series' => 'production',
	'session' => 'framework',
	'shop' => 'commercialisation',
	'storage' => 'framework',
	'user' => 'framework',
	'util' => 'framework',
	'website' => 'ouvretaferme',
]);

Package::setObservers([
	'lib' => [
		'user' => [
			'logIn' => ['company', 'farm', 'session'],
			'formLog' => ['company', 'farm'],
			'formSignUp' => ['company', 'farm'],
			'canUpdate' => ['main'],
			'sendVerifyEmail' => ['main'],
			'signUpCreate' => ['main', 'selling'],
			'close' => ['main'],
			'update' => ['selling'],
			'logOut' => ['session'],
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