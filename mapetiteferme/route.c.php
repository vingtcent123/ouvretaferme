<?php
Route::register([
	'DELETE' => [
	],
	'GET' => [
		'/minify/{version}/{filename}' => [
			'request' => 'dev/minify',
			'priority' => 5,
			'route' => ['minify', '{version}', '{filename}'],
		],
		'/presentation/engagements' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'engagements'],
		],
		'/presentation/entreprise' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'entreprise'],
		],
		'/presentation/faq' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'faq'],
		],
		'/presentation/invitation' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'invitation'],
		],
		'/presentation/legal' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'legal'],
		],
		'/presentation/pricing' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'pricing'],
		],
		'/presentation/service' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'service'],
		],
		'/robots.txt' => [
			'request' => 'main/seo',
			'priority' => 5,
			'route' => ['robots.txt'],
		],
		'/sitemap.xml' => [
			'request' => 'main/sitemap',
			'priority' => 5,
			'route' => ['sitemap.xml'],
		],
	],
	'HEAD' => [
	],
	'POST' => [
		'/@module/accounting/Account/doQuick' => [
			'request' => 'accounting/account',
			'priority' => 5,
			'route' => ['@module', 'accounting', 'Account', 'doQuick'],
		],
		'/@module/accounting/Account/quick' => [
			'request' => 'accounting/account',
			'priority' => 5,
			'route' => ['@module', 'accounting', 'Account', 'quick'],
		],
		'/@module/bank/BankAccount/doQuick' => [
			'request' => 'bank/account',
			'priority' => 5,
			'route' => ['@module', 'bank', 'BankAccount', 'doQuick'],
		],
		'/@module/bank/BankAccount/quick' => [
			'request' => 'bank/account',
			'priority' => 5,
			'route' => ['@module', 'bank', 'BankAccount', 'quick'],
		],
		'/@module/journal/Operation/doQuick' => [
			'request' => 'journal/operation',
			'priority' => 5,
			'route' => ['@module', 'journal', 'Operation', 'doQuick'],
		],
		'/@module/journal/Operation/quick' => [
			'request' => 'journal/operation',
			'priority' => 5,
			'route' => ['@module', 'journal', 'Operation', 'quick'],
		],
		'/@module/journal/ThirdParty/doQuick' => [
			'request' => 'journal/thirdParty',
			'priority' => 5,
			'route' => ['@module', 'journal', 'ThirdParty', 'doQuick'],
		],
		'/@module/journal/ThirdParty/quick' => [
			'request' => 'journal/thirdParty',
			'priority' => 5,
			'route' => ['@module', 'journal', 'ThirdParty', 'quick'],
		],
	],
	'PUT' => [
	],
]);
?>