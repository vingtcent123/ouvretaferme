<?php
Package::setList([
	'main' => 'mapetiteferme',
	'core' => 'framework',
	'dev' => 'framework',
	'editor' => 'framework',
	'example' => 'framework',
	'language' => 'framework',
	'session' => 'framework',
	'storage' => 'framework',
	'user' => 'framework',
	'util' => 'framework',
	'account' => 'accounting',
	'asset' => 'accounting',
	'bank' => 'accounting',
	'company' => 'accounting',
	'journal' => 'accounting',
	'overview' => 'accounting',
	'pdf' => 'accounting',
	'payment' => 'commercialisation',
	'selling' => 'commercialisation',
	'shop' => 'commercialisation',
	'gallery' => 'production',
	'map' => 'production',
	'plant' => 'production',
	'sequence' => 'production',
	'series' => 'production',
	'farm' => 'base',
	'hr' => 'base',
	'mail' => 'base',
	'media' => 'base',
]);

Package::setObservers([
	'lib' => [
		'user' => [
			'sendVerifyEmail' => ['main'],
			'signUpCreate' => ['main', 'selling'],
			'close' => ['main'],
			'logIn' => ['session', 'company', 'farm'],
			'logOut' => ['session'],
			'formLog' => ['company', 'farm'],
			'formSignUp' => ['company', 'farm'],
			'update' => ['selling'],
		],
		'shop' => [
			'saleConfirmed' => ['shop'],
			'saleUpdated' => ['shop'],
			'salePaid' => ['shop'],
			'saleFailed' => ['shop'],
			'saleCanceled' => ['shop'],
		],
		'lime' => [
			'loadConf' => ['media'],
		],
	],
	'ui' => [
		'user' => [
			'emailSignUp' => ['main'],
		],
	],
]);
?>