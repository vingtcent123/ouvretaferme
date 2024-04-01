<?php
Route::register([
	'DELETE' => [
	],
	'GET' => [
		'/client/{id}' => [
			'request' => 'selling/customer',
			'priority' => 5,
			'route' => ['client', '{id}'],
		],
		'/client/{id}/optIn' => [
			'request' => 'selling/customer',
			'priority' => 5,
			'route' => ['client', '{id}', 'optIn'],
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
		'/espece/{id@int}' => [
			'request' => 'plant/plant',
			'priority' => 5,
			'route' => ['espece', '{id@int}'],
		],
		'/facture/{id}' => [
			'request' => 'selling/invoice',
			'priority' => 5,
			'route' => ['facture', '{id}'],
		],
		'/famille/{fqn@fqn}' => [
			'request' => 'plant/family',
			'priority' => 5,
			'route' => ['famille', '{fqn@fqn}'],
		],
		'/ferme/{farm}/boutique/{shop}/date/{date}/product:create' => [
			'request' => 'shop/product',
			'priority' => 5,
			'route' => ['ferme', '{farm}', 'boutique', '{shop}', 'date', '{date}', 'product:create'],
		],
		'/ferme/{farm}/boutique/{shop}/date/{id}' => [
			'request' => 'shop/date',
			'priority' => 5,
			'route' => ['ferme', '{farm}', 'boutique', '{shop}', 'date', '{id}'],
		],
		'/ferme/{farm}/famille/{family}' => [
			'request' => 'plant/family',
			'priority' => 5,
			'route' => ['ferme', '{farm}', 'famille', '{family}'],
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
		'/ferme/{id}/especes' => [
			'request' => 'farm/index',
			'priority' => 5,
			'route' => ['ferme', '{id}', 'especes'],
		],
		'/ferme/{id}/especes/{status}' => [
			'request' => 'farm/index',
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
		'/ferme/{id}/optIn' => [
			'request' => 'selling/customer',
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
		'/itineraire/{id}' => [
			'request' => 'production/sequence',
			'priority' => 5,
			'route' => ['itineraire', '{id}'],
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
		'/presentation/legal' => [
			'request' => 'main/index',
			'priority' => 5,
			'route' => ['presentation', 'legal'],
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
		'/public/{domain}/{page}' => [
			'request' => 'website/public',
			'priority' => 5,
			'route' => ['public', '{domain}', '{page}'],
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
		'/shop/public/robots.txt' => [
			'request' => 'shop/public',
			'priority' => 1,
			'route' => ['shop', 'public', 'robots.txt'],
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
		'/shop/public/{id}' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{id}'],
		],
		'/shop/public/{id}/{date}' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{id}', '{date}'],
		],
		'/shop/public/{id}:conditions' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{id}:conditions'],
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
		'/vente/{id}/bon-livraison' => [
			'request' => 'selling/sale',
			'priority' => 5,
			'route' => ['vente', '{id}', 'bon-livraison'],
		],
		'/vente/{id}/devis' => [
			'request' => 'selling/sale',
			'priority' => 5,
			'route' => ['vente', '{id}', 'devis'],
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
		'/@module/production/Crop/doQuick' => [
			'request' => 'production/crop',
			'priority' => 5,
			'route' => ['@module', 'production', 'Crop', 'doQuick'],
		],
		'/@module/production/Crop/quick' => [
			'request' => 'production/crop',
			'priority' => 5,
			'route' => ['@module', 'production', 'Crop', 'quick'],
		],
		'/@module/production/Sequence/doQuick' => [
			'request' => 'production/sequence',
			'priority' => 5,
			'route' => ['@module', 'production', 'Sequence', 'doQuick'],
		],
		'/@module/production/Sequence/quick' => [
			'request' => 'production/sequence',
			'priority' => 5,
			'route' => ['@module', 'production', 'Sequence', 'quick'],
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
		'/shop/public/{fqn}/{date}/:doCancelSale' => [
			'request' => 'shop/public',
			'priority' => 5,
			'route' => ['shop', 'public', '{fqn}', '{date}', ':doCancelSale'],
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
	],
	'PUT' => [
	],
]);
?>