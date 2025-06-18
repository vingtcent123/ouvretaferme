<?php
Route::register([
	'DELETE' => [
	],
	'GET' => [
		'/ferme/{id}/analyses/cultures' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'cultures'],
		],
		'/ferme/{id}/analyses/cultures/{season}/{category}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'cultures', '{season}', '{category}'],
		],
		'/ferme/{id}/analyses/planning' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'planning'],
		],
		'/ferme/{id}/analyses/planning/{year}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'planning', '{year}'],
		],
		'/ferme/{id}/analyses/planning/{year}/{category}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'planning', '{year}', '{category}'],
		],
		'/ferme/{id}/analyses/rapports' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'rapports'],
		],
		'/ferme/{id}/analyses/rapports/{season}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'rapports', '{season}'],
		],
		'/ferme/{id}/analyses/ventes' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'ventes'],
		],
		'/ferme/{id}/analyses/ventes/{year}/{category}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'ventes', '{year}', '{category}'],
		],
		'/ferme/{id}/analyses/ventes/{year}/{category}/compare/{compare}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'analyses', 'ventes', '{year}', '{category}', 'compare', '{compare}'],
		],
		'/ferme/{id}/assolement' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'assolement'],
		],
		'/ferme/{id}/assolement/{season}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'assolement', '{season}'],
		],
		'/ferme/{id}/boutiques' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'boutiques'],
		],
		'/ferme/{id}/carte' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'carte'],
		],
		'/ferme/{id}/carte/{season}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'carte', '{season}'],
		],
		'/ferme/{id}/catalogues' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'catalogues'],
		],
		'/ferme/{id}/clients' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'clients'],
		],
		'/ferme/{id}/configuration' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'configuration'],
		],
		'/ferme/{id}/etiquettes' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'etiquettes'],
		],
		'/ferme/{id}/factures' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'factures'],
		],
		'/ferme/{id}/itineraires' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'itineraires'],
		],
		'/ferme/{id}/itineraires/{status}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'itineraires', '{status}'],
		],
		'/ferme/{id}/livraison' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'livraison'],
		],
		'/ferme/{id}/planning/{view}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'planning', '{view}'],
		],
		'/ferme/{id}/planning/{view}/{period}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'planning', '{view}', '{period}'],
		],
		'/ferme/{id}/planning/{view}/{period}/{subPeriod}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'planning', '{view}', '{period}', '{subPeriod}'],
		],
		'/ferme/{id}/produits' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'produits'],
		],
		'/ferme/{id}/rotation' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'rotation'],
		],
		'/ferme/{id}/rotation/{season}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'rotation', '{season}'],
		],
		'/ferme/{id}/series' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'series'],
		],
		'/ferme/{id}/series/{season}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'series', '{season}'],
		],
		'/ferme/{id}/stocks' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'stocks'],
		],
		'/ferme/{id}/taches/{week}/{action}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'taches', '{week}', '{action}'],
		],
		'/ferme/{id}/ventes' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'ventes'],
		],
		'/ferme/{id}/ventes/particuliers' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'ventes', 'particuliers'],
		],
		'/ferme/{id}/ventes/professionnels' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'ventes', 'professionnels'],
		],
		'/in/{key}' => [
			'request' => 'farm/invite',
			'priority' => 5,
			'route' => ['in', '{key}'],
		],
		'/minify/{version}/{filename}' => [
			'request' => 'dev/minify',
			'priority' => 5,
			'route' => ['minify', '{version}', '{filename}'],
		],
		'/outil/{id@int}' => [
			'request' => 'farm/tool',
			'priority' => 5,
			'route' => ['outil', '{id@int}'],
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
		'/@module/bank/Account/doQuick' => [
			'request' => 'bank/account',
			'priority' => 5,
			'route' => ['@module', 'bank', 'Account', 'doQuick'],
		],
		'/@module/bank/Account/quick' => [
			'request' => 'bank/account',
			'priority' => 5,
			'route' => ['@module', 'bank', 'Account', 'quick'],
		],
		'/@module/farm/Farmer/doQuick' => [
			'request' => 'farm/farmer',
			'priority' => 5,
			'route' => ['@module', 'farm', 'Farmer', 'doQuick'],
		],
		'/@module/farm/Farmer/quick' => [
			'request' => 'farm/farmer',
			'priority' => 5,
			'route' => ['@module', 'farm', 'Farmer', 'quick'],
		],
		'/@module/farm/Method/doQuick' => [
			'request' => 'farm/method',
			'priority' => 5,
			'route' => ['@module', 'farm', 'Method', 'doQuick'],
		],
		'/@module/farm/Method/quick' => [
			'request' => 'farm/method',
			'priority' => 5,
			'route' => ['@module', 'farm', 'Method', 'quick'],
		],
		'/@module/farm/Supplier/doQuick' => [
			'request' => 'farm/supplier',
			'priority' => 5,
			'route' => ['@module', 'farm', 'Supplier', 'doQuick'],
		],
		'/@module/farm/Supplier/quick' => [
			'request' => 'farm/supplier',
			'priority' => 5,
			'route' => ['@module', 'farm', 'Supplier', 'quick'],
		],
		'/@module/farm/Tool/doQuick' => [
			'request' => 'farm/tool',
			'priority' => 5,
			'route' => ['@module', 'farm', 'Tool', 'doQuick'],
		],
		'/@module/farm/Tool/quick' => [
			'request' => 'farm/tool',
			'priority' => 5,
			'route' => ['@module', 'farm', 'Tool', 'quick'],
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