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
	'media' => 'mapetiteferme',
	'farm' => 'base',
	'hr' => 'base',
	'mail' => 'base',
	'accounting' => 'accounting',
	'analyze' => 'accounting',
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
			'sendVerifyEmail' => ['main'],
			'signUpCreate' => ['main'],
			'close' => ['main'],
			'logIn' => ['session', 'farm', 'company'],
			'logOut' => ['session'],
			'formLog' => ['farm', 'company'],
			'formSignUp' => ['farm', 'company'],
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