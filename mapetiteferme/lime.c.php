<?php
Lime::setUrls([
  'dev' => 'http://www.mapetiteferme.fr',
  'prod' => 'https://www.mapetiteferme.app',
]);

Lime::setName( 'MaPetiteFerme');
Lime::setApps(['framework', 'mapetiteferme', 'accounting', 'commercialisation', 'production', 'base']);

L::setLang('fr_FR');
L::setVariables([
  'siteName' => 'MaPetiteFerme',
]);

require_once Lime::getPath().'/secret.c.php';

switch(LIME_ENV) {

  case 'prod' :

    Setting::set('dev\minify', TRUE);
    Asset::setVersion(hash_file('crc32', LIME_DIRECTORY.'/.git/FETCH_HEAD'));

    Database::setPackages([
      'company' => 'mapetiteferme',
      'dev' => 'mapetiteferme',
      'mail' => 'mapetiteferme',
      'main' => 'mapetiteferme',
      'media' => 'mapetiteferme',
      'util' => 'mapetiteferme',
      'session' => 'mapetiteferme',
      'storage' => 'mapetiteferme',

      'user' => 'ouvretaferme',
      'farm' => 'ouvretaferme',
      'map' => 'ouvretaferme',
      'series' => 'ouvretaferme',
      'plant' => 'ouvretaferme',

      'hr' => 'ouvretaferme',
    ]);

    break;

  case 'dev' :

    Database::setDebug(get_exists('sql'));

    Database::addPackages([
      'company' => 'dev_mapetiteferme',
      'dev' => 'dev_mapetiteferme',
      'mail' => 'dev_mapetiteferme',
      'main' => 'dev_mapetiteferme',
      'media' => 'dev_mapetiteferme',
      'util' => 'dev_mapetiteferme',
      'session' => 'dev_mapetiteferme',
      'storage' => 'dev_mapetiteferme',

      'user' => 'dev_ouvretaferme',
      'farm' => 'dev_ouvretaferme',
      'map' => 'dev_ouvretaferme',
      'series' => 'dev_ouvretaferme',
      'plant' => 'dev_ouvretaferme',

      'hr' => 'dev_ouvretaferme',
    ]);

    break;

}

Feature::set('user\ban', TRUE);
Setting::set('user\signUpRoles', ['employee']);
Setting::set('user\signUpView', 'main/index:signUp');

if(LIME_HOST !== NULL) {
  define('OTF_DEMO', str_starts_with(LIME_HOST, 'demo.'));
} else {
  define('OTF_DEMO', FALSE);
}

define('ACTION_FERTILISATION', 'fertilisation');
define('ACTION_RECOLTE', 'recolte');
define('ACTION_SEMIS_PEPINIERE', 'semis-pepiniere');
define('ACTION_SEMIS_DIRECT', 'semis-direct');
define('ACTION_PLANTATION', 'plantation');


Page::construct(function($data) {

  \main\PageLib::common($data);

});

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
