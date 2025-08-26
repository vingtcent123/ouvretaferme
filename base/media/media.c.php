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

function getMediaBanner() {

	return [
		'imageFormat' => [
			's' => [240, 80],
			'l' => [1800, 600]
		],
		'imageResizeReference' => ['l'],
		'imageOutputType' => [IMAGETYPE_JPEG],
		'imageMaxLength' => 1800,
		'imageRequiredSize' => 'l'
	];

}

Setting::register('media', [

	// Max size of an image in Mo (change also rewrite.cfg if needed : client_max_body_size 20m;)
	'maxImageSize' => 20,

	'images' => ['user-vignette', 'editor', 'plant-vignette', 'gallery', 'farm-vignette', 'farm-logo', 'farm-banner', 'product-vignette', 'tool-vignette', 'website-logo', 'website-favicon', 'website-banner', 'webpage-banner', 'shop-logo', 'pdf-content'],

	'user-vignette' => [
		'class' => 'UserVignette',
		'element' => 'user\User',
		'field' => 'vignette'
	] + getMediaVignette(),

	'shop-logo' => [
		'class' => 'ShopLogo',
		'element' => 'shop\Shop',
		'field' => 'logo'
	] + getMediaLogo(),

	'pdf-content' => [
		'class' => 'PdfContent',
		'element' => 'selling\PdfContent',
		'field' => 'hash'
	],

	'editor' => [
		'class' => 'Editor',
		'element' => NULL,
		'field' => NULL,
		'imageFormat' => [
			'xs' => 100,
			's' => 500,
			'm' => 750,
			'l' => 1000,
		],
		'imageCropReference' => (int)(2000 / 1.1),
		'imageResizeReference' => ['l', 's'],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF],
		'imageMaxLength' => 2000,
		'imageMinPixels' => 10000000,
		'imageRequiredSize' => 50,
	],

	'plant-vignette' => [
		'class' => 'PlantVignette',
		'element' => 'plant\Plant',
		'field' => 'vignette',
	] + getMediaVignette(),

	'product-vignette' => [
		'class' => 'ProductVignette',
		'element' => 'selling\Product',
		'field' => 'vignette',
	] + getMediaVignette(),

	'tool-vignette' => [
		'class' => 'ToolVignette',
		'element' => 'farm\Tool',
		'field' => 'vignette',
		'imageFormat' => [
			's' => 64,
			'm' => 256,
			'l' => 1024
		],
		'imageResizeReference' => ['l'],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG],
		'imageMaxLength' => 2000,
		'imageRequiredSize' => 'm',
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

	'farm-banner' => [
		'class' => 'FarmBanner',
		'element' => 'farm\Farm',
		'field' => 'emailBanner',
		'imageFormat' => [
			'm' => [500, 100]
		],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG],
		'imageMaxLength' => 500,
		'imageRequiredSize' => 'm',
	],

	'farm-logo' => [
		'class' => 'FarmLogo',
		'element' => 'farm\Farm',
		'field' => 'logo'
	] + getMediaLogo(),

	'farm-vignette' => [
		'class' => 'FarmVignette',
		'element' => 'farm\Farm',
		'field' => 'vignette'
	] + getMediaVignette(),

	'website-favicon' => [
		'class' => 'WebsiteFavicon',
		'element' => 'website\Website',
		'field' => 'favicon',
		'imageFormat' => [
			's' => [64, 64],
			'm' => [128, 128],
			'l' => [256, 256]
		],
		'imageResizeReference' => ['l'],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG],
		'imageMaxLength' => 1024,
		'imageRequiredSize' => 'l'
	],

	'website-logo' => [
		'class' => 'WebsiteLogo',
		'element' => 'website\Website',
		'field' => 'logo'
	] + getMediaLogo(),

	'website-banner' => [
		'class' => 'WebsiteBanner',
		'element' => 'website\Website',
		'field' => 'banner'
	] + getMediaBanner(),

	'webpage-banner' => [
		'class' => 'WebpageBanner',
		'element' => 'website\Webpage',
		'field' => 'banner'
	] + getMediaBanner(),

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

	'basePath' => '/var/www/storage',

	'mediaDriver' => function () {
		return new \storage\DriverLib();
	},

	// URL of the images
	'mediaUrl' => function() {

		$driver = Setting::get('mediaDriver');

		if($driver instanceof \storage\DriverLib) {

			if(LIME_ENV === 'dev') {
				return 'http://media.dev-ouvretaferme.org';
			} else {
				return 'https://media.ouvretaferme.org';
			}


		}


	}

]);

Setting::copy('media', 'storage');
?>
