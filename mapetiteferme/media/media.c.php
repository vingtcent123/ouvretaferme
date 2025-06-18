<?php

function getMediaVignette() {

	return [
		'imageFormat' => [
			's' => [64, 64],
			'm' => [256, 256],
			'l' => [512, 512]
		],
		'imageResizeReference' => ['l'],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG],
		'imageMaxLength' => 1024,
		'imageRequiredSize' => 'l',
		'imageCropShape' => 'circle'
	];

}

function getMediaLogo() {

	return [
		'imageFormat' => [
			's' => 64,
			'm' => 256,
			'l' => 512
		],
		'imageResizeReference' => ['l'],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG],
		'imageMaxLength' => 1024,
		'imageRequiredSize' => 'l'
	];

}

/*
Type example:

	// Images possible sizes
	// They must be sorted from the smallest to the biggest
	'imageFormat' => [
		'xs' => [25, 25],
		's' => [40, 40],
	],

	// Pour les formats définis avec seulement 1 valeur :
	// Si non défini : contrainte sur le plus grand côté
	// Si défini : contrainte sur width ou height selon ce qui est précisé
	'imageFormatConstraint' => [
	],

	// Image override quality (default is 85)
	'imageQuality' => [
		'xs' => [
			IMAGETYPE_JPEG => 100
		],
		's' => [
			IMAGETYPE_JPEG => 75
		]
	],

	// Reference size for image cropping
	'imageCropReference' => 2000,

	// Reference size for image resizing
	'imageResizeReference' => ['l', 's'],

	'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG],

	// Taille maximale du plus grand côté de l'image originale à enregistrer
	// Mandatory value
	'imageMaxLength' => 4000,

	'imageMinPixels' => 10000000,

	'imageRequiredSize' => 'l'|250,

*/

Setting::register('media', [

	// Max size of an image in Mo (change also rewrite.cfg if needed : client_max_body_size 20m;)
	'maxImageSize' => 20,

	'images' => ['user-vignette', 'editor', 'gallery', 'company-vignette', 'company-logo', 'company-banner', 'pdf-content'],

	'user-vignette' => [
		'class' => 'UserVignette',
		'element' => 'user\User',
		'field' => 'vignette'
	] + getMediaVignette(),

	'editor' => [
		'class' => 'Editor',
		'element' => NULL,
		'field' => NULL,
		'imageFormat' => [
			'xs' => 100,
			's' => 500,
			'm' => 750,
			'l' => 1000,
			'xl' => 2000
		],
		'imageCropReference' => (int)(2000 / 1.1),
		'imageResizeReference' => ['xl', 's'],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF],
		'imageMaxLength' => 5000,
		'imageMinPixels' => 10000000,
		'imageRequiredSize' => 50,
	],

	'gallery' => [
		'class' => 'Gallery',
		'element' => NULL,
		'field' => NULL,
		'imageFormat' => [
			'm' => 500
		],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG],
		'imageMaxLength' => 1000,
		'imageRequiredSize' => 200,
	],

	'company-banner' => [
		'class' => 'companyBanner',
		'element' => 'company\Company',
		'field' => 'banner',
		'imageFormat' => [
			'm' => [500, 100]
		],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG],
		'imageMaxLength' => 500,
		'imageRequiredSize' => 'm',
	],

	'company-logo' => [
		'class' => 'CompanyLogo',
		'element' => 'company\Company',
		'field' => 'logo'
	] + getMediaLogo(),

	'company-vignette' => [
		'class' => 'CompanyVignette',
		'element' => 'company\Company',
		'field' => 'vignette'
	] + getMediaVignette(),

	'pdf-content' => [
		'class' => 'Content',
		'element' => 'pdf\Content',
		'field' => 'hash'
	],

	'imageCropRequiredFactor' => 1.1,
	'imageResizeRequiredFactor' => 1.15,

	'defaultQuality' => [
		IMAGETYPE_JPEG => 85,
		IMAGETYPE_PNG => 9,
		IMAGETYPE_GIF => NULL
	],

	'imagesInputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP],

	'imagesExtensions' => [
		'p' => 'png',
		'j' => 'jpg',
		'g' => 'gif',
		'f' => 'pdf',
	],

	'mediaDriver' => function () {

		return new \storage\DriverLib();

	},

	// URL of the images
	'mediaUrl' => function() { // TODO OVH : à renommer

		$driver = Setting::get('mediaDriver');

		if($driver instanceof \storage\DriverLib) {

			if(LIME_ENV === 'dev') {
				return 'http://media.mapetiteferme.fr';
			}

			return 'https://media.mapetiteferme.app';

		}

		return null;

	},

	'basePath' => '/var/www/mpf-storage',

]);

Setting::copy('media', 'storage');
?>
