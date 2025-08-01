<?php
Lime::setUrls([
	'dev' => 'http://www.dev-ouvretaferme.org',
	'prod' => 'https://www.ouvretaferme.org'
]);

Lime::setApps(['framework', 'agora', 'ouvretaferme', 'base', 'commercialisation', 'production', 'accounting']);

L::setLang('fr_FR');
L::setVariables([
	'siteName' => 'Ouvretaferme',
]);

Lime::setName('Ouvretaferme');

define('FEATURE_ACCOUNTING', LIME_ENV === 'dev');

require_once Lime::getPath().'/secret.c.php';

switch(LIME_ENV) {

	case 'prod' :

		Setting::set('dev\minify', TRUE);
		Asset::setVersion(hash_file('crc32', LIME_DIRECTORY.'/.git/FETCH_HEAD'));

		Database::setPackages([
			'dev' => 'ouvretaferme',
			'shop' => 'ouvretaferme',
			'util' => 'ouvretaferme',
			'session' => 'ouvretaferme',
			'mail' => 'ouvretaferme',
			'user' => 'ouvretaferme',
			'media' => 'ouvretaferme',
			'payment' => 'ouvretaferme',
			'storage' => 'ouvretaferme',
			'plant' => 'ouvretaferme',
			'gallery' => 'ouvretaferme',
			'sequence' => 'ouvretaferme',
			'farm' => 'ouvretaferme',
			'map' => 'ouvretaferme',
			'series' => 'ouvretaferme',
			'analyze' => 'ouvretaferme',
			'hr' => 'ouvretaferme',
			'selling' => 'ouvretaferme',
			'website' => 'ouvretaferme',

			'accounting' => 'mapetiteferme',
			'company' => 'mapetiteferme',
			'account' => 'mapetiteferme',
			'bank' => 'mapetiteferme',
		]);

		break;

	case 'dev' :

		Setting::set('dev\minify', FALSE);
		Database::setDebug(get_exists('sql'));

		Database::setPackages([
			'dev' => 'dev_ouvretaferme',
			'shop' => 'dev_ouvretaferme',
			'util' => 'dev_ouvretaferme',
			'session' => 'dev_ouvretaferme',
			'mail' => 'dev_ouvretaferme',
			'user' => 'dev_ouvretaferme',
			'media' => 'dev_ouvretaferme',
			'payment' => 'dev_ouvretaferme',
			'storage' => 'dev_ouvretaferme',
			'plant' => 'dev_ouvretaferme',
			'gallery' => 'dev_ouvretaferme',
			'sequence' => 'dev_ouvretaferme',
			'farm' => 'dev_ouvretaferme',
			'map' => 'dev_ouvretaferme',
			'series' => 'dev_ouvretaferme',
			'analyze' => 'dev_ouvretaferme',
			'hr' => 'dev_ouvretaferme',
			'selling' => 'dev_ouvretaferme',
			'website' => 'dev_ouvretaferme',

			'account' => 'dev_mapetiteferme',
			'accounting' => 'dev_mapetiteferme',
			'company' => 'dev_mapetiteferme',
			'bank' => 'dev_mapetiteferme',
		]);

		break;

}

Package::setConfFile('storage', LIME_DIRECTORY.'/base/media/media.c.php');

Feature::set('user\ban', TRUE);

Setting::set('user\checkTos', TRUE);
Setting::set('user\signUpRoles', ['farmer', 'customer']);
Setting::set('user\statsRoles', ['farmer', 'customer']);
Setting::set('user\signUpView', 'main/index:signUp');

Page::construct(function($data) {

	\main\PageLib::common($data);

});

if(LIME_HOST !== NULL) {
	define('OTF_DEMO', str_starts_with(LIME_HOST, 'demo.'));
} else {
	define('OTF_DEMO', FALSE);
}

define('OTF_DEMO_HOST', 'demo.'.Lime::getDomain());
define('OTF_DEMO_URL', \Lime::getProtocol().'://'.OTF_DEMO_HOST);

define('CATEGORIE_CULTURE', 'culture');
define('CATEGORIE_PRODUCTION', 'production');

define('ACTION_FERTILISATION', 'fertilisation');
define('ACTION_RECOLTE', 'recolte');
define('ACTION_SEMIS_PEPINIERE', 'semis-pepiniere');
define('ACTION_SEMIS_DIRECT', 'semis-direct');
define('ACTION_PLANTATION', 'plantation');



function vat_from_including(float $amount, float $vatRate): float {
	return $amount - round($amount / (1 + $vatRate / 100), 2);
}

function vat_from_excluding(float $amount, float $vatRate): float {
	return round($amount * $vatRate / 100, 2);
}

function including_from_excluding(float $amount, float $vatRate): float {
	return round($amount + vat_from_excluding($amount, $vatRate), 2);
}

function excluding_from_including(float $amount, float $vatRate): float {
	return round($amount - vat_from_including($amount, $vatRate), 2);
}
?>
