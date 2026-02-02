<?php
Route::register([
	'DELETE' => [
	],
	'GET' => [
		'/adherer' => [
			'request' => 'association/membership',
			'priority' => 5,
			'route' => ['adherer'],
		],
		'/banque/imports' => [
			'request' => 'bank/import',
			'priority' => 5,
			'route' => ['banque', 'imports'],
		],
		'/banque/imports:import' => [
			'request' => 'bank/import',
			'priority' => 5,
			'route' => ['banque', 'imports:import'],
		],
		'/banque/operations' => [
			'request' => 'bank/cashflow',
			'priority' => 5,
			'route' => ['banque', 'operations'],
		],
		'/client/{id}' => [
			'request' => 'selling/customer',
			'priority' => 5,
			'route' => ['client', '{id}'],
		],
		'/commande/{id}' => [
			'request' => 'selling/order',
			'priority' => 5,
			'route' => ['commande', '{id}'],
		],
		'/commandes/particuliers' => [
			'request' => 'selling/order',
			'priority' => 5,
			'route' => ['commandes', 'particuliers'],
		],
		'/commandes/professionnels/{farm}' => [
			'request' => 'selling/order',
			'priority' => 5,
			'route' => ['commandes', 'professionnels', '{farm}'],
		],
		'/comptabilite/decouvrir' => [
			'request' => 'company/public',
			'priority' => 5,
			'route' => ['comptabilite', 'decouvrir'],
		],
		'/comptabilite/demarrer' => [
			'request' => 'company/public',
			'priority' => 5,
			'route' => ['comptabilite', 'demarrer'],
		],
		'/comptabilite/parametrer' => [
			'request' => 'company/public',
			'priority' => 5,
			'route' => ['comptabilite', 'parametrer'],
		],
		'/configuration/accounting' => [
			'request' => 'company/configuration',
			'priority' => 5,
			'route' => ['configuration', 'accounting'],
		],
		'/doc/' => [
			'request' => 'main/doc/main',
			'priority' => 5,
			'route' => ['doc'],
		],
		'/donner' => [
			'request' => 'association/donation',
			'priority' => 5,
			'route' => ['donner'],
		],
		'/espece/{id@int}' => [
			'request' => 'plant/plant',
			'priority' => 5,
			'route' => ['espece', '{id@int}'],
		],
		'/etats-financiers/' => [
			'request' => 'overview/index',
			'priority' => 5,
			'route' => ['etats-financiers'],
		],
		'/etats-financiers/declaration-de-tva/operations' => [
			'request' => 'overview/vat',
			'priority' => 5,
			'route' => ['etats-financiers', 'declaration-de-tva', 'operations'],
		],
		'/etats-financiers/{view}' => [
			'request' => 'overview/index',
			'priority' => 5,
			'route' => ['etats-financiers', '{view}'],
		],
		'/facturation-electronique' => [
			'request' => 'invoicing/index',
			'priority' => 5,
			'route' => ['facturation-electronique'],
		],
		'/facturation-electronique-les-mains-dans-les-poches' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['facturation-electronique-les-mains-dans-les-poches'],
		],
		'/facture/{id}' => [
			'request' => 'selling/invoice',
			'priority' => 5,
			'route' => ['facture', '{id}'],
		],
		'/factures/particuliers' => [
			'request' => 'selling/order',
			'priority' => 5,
			'route' => ['factures', 'particuliers'],
		],
		'/ferme/{farm}/adherer' => [
			'request' => 'association/membership',
			'priority' => 5,
			'route' => ['ferme', '{farm}', 'adherer'],
		],
		'/ferme/{farm}/donner' => [
			'request' => 'association/membership',
			'priority' => 5,
			'route' => ['ferme', '{farm}', 'donner'],
		],
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
		'/ferme/{id}/boutique/{shop}' => [
			'request' => 'shop/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'boutique', '{shop}'],
		],
		'/ferme/{id}/boutiques' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'boutiques'],
		],
		'/ferme/{id}/campagnes' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'campagnes'],
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
		'/ferme/{id}/configuration/commercialisation' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'configuration', 'commercialisation'],
		],
		'/ferme/{id}/configuration/production' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'configuration', 'production'],
		],
		'/ferme/{id}/contacts' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'contacts'],
		],
		'/ferme/{id}/date/{date}' => [
			'request' => 'shop/date',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'date', '{date}'],
		],
		'/ferme/{id}/especes' => [
			'request' => 'plant/plant',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'especes'],
		],
		'/ferme/{id}/especes/{status}' => [
			'request' => 'plant/plant',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'especes', '{status}'],
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
		'/ferme/{id}/optIn' => [
			'request' => 'mail/contact',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'optIn'],
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
		'/ferme/{id}/previsionnel' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'previsionnel'],
		],
		'/ferme/{id}/previsionnel/{season}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'previsionnel', '{season}'],
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
		'/ferme/{id}/semences-plants' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'semences-plants'],
		],
		'/ferme/{id}/semences-plants/{season}' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'semences-plants', '{season}'],
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
		'/immobilisation/{id}/' => [
			'request' => 'asset/index',
			'priority' => 5,
			'route' => ['immobilisation', '{id}'],
		],
		'/immobilisations' => [
			'request' => 'asset/amortization',
			'priority' => 5,
			'route' => ['immobilisations'],
		],
		'/immobilisations/acquisitions' => [
			'request' => 'asset/amortization',
			'priority' => 5,
			'route' => ['immobilisations', 'acquisitions'],
		],
		'/in/{key}' => [
			'request' => 'farm/invite',
			'priority' => 5,
			'route' => ['in', '{key}'],
		],
		'/itineraire/{id}' => [
			'request' => 'sequence/sequence',
			'priority' => 5,
			'route' => ['itineraire', '{id}'],
		],
		'/jouer' => [
			'request' => 'game/index',
			'priority' => 5,
			'route' => ['jouer'],
		],
		'/journal-de-caisse' => [
			'request' => 'cash/index',
			'priority' => 5,
			'route' => ['journal-de-caisse'],
		],
		'/journal/grand-livre' => [
			'request' => 'journal/book',
			'priority' => 5,
			'route' => ['journal', 'grand-livre'],
		],
		'/journal/livre-journal' => [
			'request' => 'journal/operations',
			'priority' => 5,
			'route' => ['journal', 'livre-journal'],
		],
		'/journal/operation/{id}' => [
			'request' => 'journal/operation',
			'priority' => 5,
			'route' => ['journal', 'operation', '{id}'],
		],
		'/journal/operation/{id}/update' => [
			'request' => 'journal/operation',
			'priority' => 5,
			'route' => ['journal', 'operation', '{id}', 'update'],
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
		'/pdf/{id}' => [
			'request' => 'selling/pdf',
			'priority' => 5,
			'route' => ['pdf', '{id}'],
		],
		'/precomptabilite' => [
			'request' => 'preaccounting/index',
			'priority' => 5,
			'route' => ['precomptabilite'],
		],
		'/precomptabilite/ventes' => [
			'request' => 'preaccounting/index',
			'priority' => 5,
			'route' => ['precomptabilite', 'ventes'],
		],
		'/precomptabilite/ventes:telecharger' => [
			'request' => 'preaccounting/index',
			'priority' => 5,
			'route' => ['precomptabilite', 'ventes:telecharger'],
		],
		'/precomptabilite:fec' => [
			'request' => 'preaccounting/index',
			'priority' => 5,
			'route' => ['precomptabilite:fec'],
		],
		'/precomptabilite:importer' => [
			'request' => 'preaccounting/index',
			'priority' => 5,
			'route' => ['precomptabilite:importer'],
		],
		'/precomptabilite:rapprocher' => [
			'request' => 'preaccounting/index',
			'priority' => 5,
			'route' => ['precomptabilite:rapprocher'],
		],
		'/presentation/adhesion' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'adhesion'],
		],
		'/presentation/faq' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'faq'],
		],
		'/presentation/formations' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'formations'],
		],
		'/presentation/invitation' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'invitation'],
		],
		'/presentation/producteur' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'producteur'],
		],
		'/presentation/service' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'service'],
		],
		'/produit/{id}' => [
			'request' => 'selling/product',
			'priority' => 5,
			'route' => ['produit', '{id}'],
		],
		'/public/:404' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', ':404'],
		],
		'/public/robots.txt' => [
			'request' => 'website/public',
			'priority' => 1,
			'route' => ['public', 'robots.txt'],
		],
		'/public/{domain}' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', '{domain}'],
		],
		'/public/{domain}/' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', '{domain}'],
		],
		'/public/{domain}/:test' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', '{domain}', ':test'],
		],
		'/public/{domain}/robots.txt' => [
			'request' => 'website/public',
			'priority' => 1,
			'route' => ['public', '{domain}', 'robots.txt'],
		],
		'/public/{domain}/sitemap.xml' => [
			'request' => 'website/public',
			'priority' => 1,
			'route' => ['public', '{domain}', 'sitemap.xml'],
		],
		'/public/{domain}/{destination}' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', '{domain}', '{destination}'],
		],
		'/rapport/{id}' => [
			'request' => 'analyze/report',
			'priority' => 5,
			'route' => ['rapport', '{id}'],
		],
		'/robots.txt' => [
			'request' => 'main/seo',
			'priority' => 5,
			'route' => ['robots.txt'],
		],
		'/serie/{id}' => [
			'request' => 'series/series',
			'priority' => 5,
			'route' => ['serie', '{id}'],
		],
		'/shop/public/' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public'],
		],
		'/shop/public/embed-full.js' => [
			'request' => 'shop/public',
			'priority' => 1,
			'route' => ['shop', 'public', 'embed-full.js'],
		],
		'/shop/public/embed-limited.js' => [
			'request' => 'shop/public',
			'priority' => 1,
			'route' => ['shop', 'public', 'embed-limited.js'],
		],
		'/shop/public/robots.txt' => [
			'request' => 'shop/public',
			'priority' => 1,
			'route' => ['shop', 'public', 'robots.txt'],
		],
		'/shop/public/{fqn}' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}'],
		],
		'/shop/public/{fqn}/{date}' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}'],
		],
		'/shop/public/{fqn}/{date}/confirmation' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', 'confirmation'],
		],
		'/shop/public/{fqn}/{date}/paiement' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', 'paiement'],
		],
		'/shop/public/{fqn}/{date}/panier' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', 'panier'],
		],
		'/shop/public/{fqn}:conditions' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}:conditions'],
		],
		'/sitemap.xml' => [
			'request' => 'main/sitemap',
			'priority' => 5,
			'route' => ['sitemap.xml'],
		],
		'/tache/{id}' => [
			'request' => 'series/task',
			'priority' => 5,
			'route' => ['tache', '{id}'],
		],
		'/vente/{id}' => [
			'request' => 'selling/sale',
			'priority' => 5,
			'route' => ['vente', '{id}'],
		],
		'/vente/{id}/marche' => [
			'request' => 'selling/market',
			'priority' => 5,
			'route' => ['vente', '{id}', 'marche'],
		],
		'/vente/{id}/marche/articles' => [
			'request' => 'selling/market',
			'priority' => 5,
			'route' => ['vente', '{id}', 'marche', 'articles'],
		],
		'/vente/{id}/marche/vente/{subId}' => [
			'request' => 'selling/market',
			'priority' => 5,
			'route' => ['vente', '{id}', 'marche', 'vente', '{subId}'],
		],
		'/vente/{id}/marche/ventes' => [
			'request' => 'selling/market',
			'priority' => 5,
			'route' => ['vente', '{id}', 'marche', 'ventes'],
		],
	],
	'HEAD' => [
	],
	'POST' => [
		'/@module/account/Account/doQuick' => [
			'request' => 'account/account',
			'priority' => 5,
			'route' => ['@module', 'account', 'Account', 'doQuick'],
		],
		'/@module/account/Account/quick' => [
			'request' => 'account/account',
			'priority' => 5,
			'route' => ['@module', 'account', 'Account', 'quick'],
		],
		'/@module/account/ThirdParty/doQuick' => [
			'request' => 'account/thirdParty',
			'priority' => 5,
			'route' => ['@module', 'account', 'ThirdParty', 'doQuick'],
		],
		'/@module/account/ThirdParty/quick' => [
			'request' => 'account/thirdParty',
			'priority' => 5,
			'route' => ['@module', 'account', 'ThirdParty', 'quick'],
		],
		'/@module/analyze/Cultivation/doQuick' => [
			'request' => 'analyze/cultivation',
			'priority' => 5,
			'route' => ['@module', 'analyze', 'Cultivation', 'doQuick'],
		],
		'/@module/analyze/Cultivation/quick' => [
			'request' => 'analyze/cultivation',
			'priority' => 5,
			'route' => ['@module', 'analyze', 'Cultivation', 'quick'],
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
		'/@module/journal/JournalCode/doQuick' => [
			'request' => 'journal/journalCode',
			'priority' => 5,
			'route' => ['@module', 'journal', 'JournalCode', 'doQuick'],
		],
		'/@module/journal/JournalCode/quick' => [
			'request' => 'journal/journalCode',
			'priority' => 5,
			'route' => ['@module', 'journal', 'JournalCode', 'quick'],
		],
		'/@module/map/Bed/doQuick' => [
			'request' => 'map/bed',
			'priority' => 5,
			'route' => ['@module', 'map', 'Bed', 'doQuick'],
		],
		'/@module/map/Bed/quick' => [
			'request' => 'map/bed',
			'priority' => 5,
			'route' => ['@module', 'map', 'Bed', 'quick'],
		],
		'/@module/payment/Method/doQuick' => [
			'request' => 'payment/method',
			'priority' => 5,
			'route' => ['@module', 'payment', 'Method', 'doQuick'],
		],
		'/@module/payment/Method/quick' => [
			'request' => 'payment/method',
			'priority' => 5,
			'route' => ['@module', 'payment', 'Method', 'quick'],
		],
		'/@module/plant/Forecast/doQuick' => [
			'request' => 'plant/forecast',
			'priority' => 5,
			'route' => ['@module', 'plant', 'Forecast', 'doQuick'],
		],
		'/@module/plant/Forecast/quick' => [
			'request' => 'plant/forecast',
			'priority' => 5,
			'route' => ['@module', 'plant', 'Forecast', 'quick'],
		],
		'/@module/plant/Variety/doQuick' => [
			'request' => 'plant/variety',
			'priority' => 5,
			'route' => ['@module', 'plant', 'Variety', 'doQuick'],
		],
		'/@module/plant/Variety/quick' => [
			'request' => 'plant/variety',
			'priority' => 5,
			'route' => ['@module', 'plant', 'Variety', 'quick'],
		],
		'/@module/selling/Grid/doQuick' => [
			'request' => 'selling/grid',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Grid', 'doQuick'],
		],
		'/@module/selling/Grid/quick' => [
			'request' => 'selling/grid',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Grid', 'quick'],
		],
		'/@module/selling/Invoice/doQuick' => [
			'request' => 'selling/invoice',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Invoice', 'doQuick'],
		],
		'/@module/selling/Invoice/quick' => [
			'request' => 'selling/invoice',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Invoice', 'quick'],
		],
		'/@module/selling/Item/doQuick' => [
			'request' => 'selling/item',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Item', 'doQuick'],
		],
		'/@module/selling/Item/quick' => [
			'request' => 'selling/item',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Item', 'quick'],
		],
		'/@module/selling/Payment/doQuick' => [
			'request' => 'selling/payment',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Payment', 'doQuick'],
		],
		'/@module/selling/Payment/quick' => [
			'request' => 'selling/payment',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Payment', 'quick'],
		],
		'/@module/selling/Product/doQuick' => [
			'request' => 'selling/product',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Product', 'doQuick'],
		],
		'/@module/selling/Product/quick' => [
			'request' => 'selling/product',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Product', 'quick'],
		],
		'/@module/selling/Sale/doQuick' => [
			'request' => 'selling/sale',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Sale', 'doQuick'],
		],
		'/@module/selling/Sale/quick' => [
			'request' => 'selling/sale',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Sale', 'quick'],
		],
		'/@module/selling/Unit/doQuick' => [
			'request' => 'selling/unit',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Unit', 'doQuick'],
		],
		'/@module/selling/Unit/quick' => [
			'request' => 'selling/unit',
			'priority' => 5,
			'route' => ['@module', 'selling', 'Unit', 'quick'],
		],
		'/@module/sequence/Crop/doQuick' => [
			'request' => 'sequence/crop',
			'priority' => 5,
			'route' => ['@module', 'sequence', 'Crop', 'doQuick'],
		],
		'/@module/sequence/Crop/quick' => [
			'request' => 'sequence/crop',
			'priority' => 5,
			'route' => ['@module', 'sequence', 'Crop', 'quick'],
		],
		'/@module/sequence/Sequence/doQuick' => [
			'request' => 'sequence/sequence',
			'priority' => 5,
			'route' => ['@module', 'sequence', 'Sequence', 'doQuick'],
		],
		'/@module/sequence/Sequence/quick' => [
			'request' => 'sequence/sequence',
			'priority' => 5,
			'route' => ['@module', 'sequence', 'Sequence', 'quick'],
		],
		'/@module/series/Cultivation/doQuick' => [
			'request' => 'series/cultivation',
			'priority' => 5,
			'route' => ['@module', 'series', 'Cultivation', 'doQuick'],
		],
		'/@module/series/Cultivation/quick' => [
			'request' => 'series/cultivation',
			'priority' => 5,
			'route' => ['@module', 'series', 'Cultivation', 'quick'],
		],
		'/@module/series/Series/doQuick' => [
			'request' => 'series/series',
			'priority' => 5,
			'route' => ['@module', 'series', 'Series', 'doQuick'],
		],
		'/@module/series/Series/quick' => [
			'request' => 'series/series',
			'priority' => 5,
			'route' => ['@module', 'series', 'Series', 'quick'],
		],
		'/@module/series/Task/doQuick' => [
			'request' => 'series/task',
			'priority' => 5,
			'route' => ['@module', 'series', 'Task', 'doQuick'],
		],
		'/@module/series/Task/quick' => [
			'request' => 'series/task',
			'priority' => 5,
			'route' => ['@module', 'series', 'Task', 'quick'],
		],
		'/@module/shop/Department/doQuick' => [
			'request' => 'shop/department',
			'priority' => 5,
			'route' => ['@module', 'shop', 'Department', 'doQuick'],
		],
		'/@module/shop/Department/quick' => [
			'request' => 'shop/department',
			'priority' => 5,
			'route' => ['@module', 'shop', 'Department', 'quick'],
		],
		'/@module/shop/Product/doQuick' => [
			'request' => 'shop/product',
			'priority' => 5,
			'route' => ['@module', 'shop', 'Product', 'doQuick'],
		],
		'/@module/shop/Product/quick' => [
			'request' => 'shop/product',
			'priority' => 5,
			'route' => ['@module', 'shop', 'Product', 'quick'],
		],
		'/@module/shop/Share/doQuick' => [
			'request' => 'shop/share',
			'priority' => 5,
			'route' => ['@module', 'shop', 'Share', 'doQuick'],
		],
		'/@module/shop/Share/quick' => [
			'request' => 'shop/share',
			'priority' => 5,
			'route' => ['@module', 'shop', 'Share', 'quick'],
		],
		'/@module/website/Menu/doQuick' => [
			'request' => 'website/menu',
			'priority' => 5,
			'route' => ['@module', 'website', 'Menu', 'doQuick'],
		],
		'/@module/website/Menu/quick' => [
			'request' => 'website/menu',
			'priority' => 5,
			'route' => ['@module', 'website', 'Menu', 'quick'],
		],
		'/@module/website/News/doQuick' => [
			'request' => 'website/news',
			'priority' => 5,
			'route' => ['@module', 'website', 'News', 'doQuick'],
		],
		'/@module/website/News/quick' => [
			'request' => 'website/news',
			'priority' => 5,
			'route' => ['@module', 'website', 'News', 'quick'],
		],
		'/banque/imports:doImport' => [
			'request' => 'bank/import',
			'priority' => 5,
			'route' => ['banque', 'imports:doImport'],
		],
		'/journal/operation/{id}/doUpdate' => [
			'request' => 'journal/operation',
			'priority' => 5,
			'route' => ['journal', 'operation', '{id}', 'doUpdate'],
		],
		'/public/{domain}/:doContact' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', '{domain}', ':doContact'],
		],
		'/public/{domain}/:doDonate' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', '{domain}', ':doDonate'],
		],
		'/public/{domain}/:doNewsletter' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', '{domain}', ':doNewsletter'],
		],
		'/shop/public/{fqn}/{date}/:doCancelCustomer' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':doCancelCustomer'],
		],
		'/shop/public/{fqn}/{date}/:doCreatePayment' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':doCreatePayment'],
		],
		'/shop/public/{fqn}/{date}/:doCreateSale' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':doCreateSale'],
		],
		'/shop/public/{fqn}/{date}/:doUpdateAddress' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':doUpdateAddress'],
		],
		'/shop/public/{fqn}/{date}/:doUpdateBasket' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':doUpdateBasket'],
		],
		'/shop/public/{fqn}/{date}/:doUpdatePayment' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':doUpdatePayment'],
		],
		'/shop/public/{fqn}/{date}/:doUpdatePhone' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':doUpdatePhone'],
		],
		'/shop/public/{fqn}/{date}/:getBasket' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':getBasket'],
		],
		'/shop/public/{fqn}/{date}/paiement' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', 'paiement'],
		],
		'/vat/doCreateOperations' => [
			'request' => 'overview/vat',
			'priority' => 5,
			'route' => ['vat', 'doCreateOperations'],
		],
		'/vat/doDeclare' => [
			'request' => 'overview/vat',
			'priority' => 5,
			'route' => ['vat', 'doDeclare'],
		],
		'/vat/reset' => [
			'request' => 'overview/vat',
			'priority' => 5,
			'route' => ['vat', 'reset'],
		],
		'/vat/saveCerfa' => [
			'request' => 'overview/vat',
			'priority' => 5,
			'route' => ['vat', 'saveCerfa'],
		],
	],
	'PUT' => [
	],
]);
?>