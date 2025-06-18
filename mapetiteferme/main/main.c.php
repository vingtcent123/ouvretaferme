<?php

Setting::register('main', [

	'maintenance' => FALSE,

	'robotsDisallow' => '',

	'viewSettings' => 'settings',
	'viewJournal' => 'journal',
	'viewAsset' => 'asset',
	'viewBank' => 'cashflow',
	'viewAnalyze' => 'bank',
	'viewOverview' => 'balance',

	'remoteKey' => 'toto',

	'otfUrl' => match(LIME_ENV) {
		'prod' => 'https://www.ouvretaferme.org',
		'dev' => 'http://www.dev-ouvretaferme.org',
		'demo' => 'https://demo.ouvretaferme.org',
	},

	'backupServer' => [
		'user' => '',
		'hostname' => '',
	],

]);
?>
