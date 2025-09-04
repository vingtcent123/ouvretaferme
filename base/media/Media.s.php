<?php
namespace media;

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

class MediaSetting extends \Settings {

	// Max size of an image in Mo (change also rewrite.cfg if needed : client_max_body_size 20m;)
	const MAX_IMAGE_SIZE = 20;

	public static $images = ['user-vignette', 'editor', 'plant-vignette', 'gallery', 'farm-vignette', 'farm-logo', 'farm-banner', 'product-vignette', 'tool-vignette', 'website-logo', 'website-favicon', 'website-banner', 'webpage-banner', 'shop-logo', 'pdf-content', 'association-document'];

	public static $types;

	const IMAGE_CROP_REQUIRED_FACTOR = 1.1;

	const IMAGE_RESIZE_REQUIRED_FACTOR = 1.15;

	const DEFAULT_QUALITY = [
		IMAGETYPE_JPEG => 85,
		IMAGETYPE_PNG => 9,
		IMAGETYPE_GIF => NULL
	];

	const IMAGES_INPUT_TYPE = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP];

	const IMAGES_EXTENSIONS = [
		'p' => 'png',
		'j' => 'jpg',
		'g' => 'gif',
		'f' => 'pdf',
	];

	const BASE_PATH = '/var/www/storage';

	public static $mediaDriver;

}

MediaSetting::$mediaDriver = new \storage\DriverLib();
function mediaUrl () {

	$driver = MediaSetting::$mediaDriver;

	if($driver instanceof \storage\DriverLib) {

		if(LIME_ENV === 'dev') {
			return 'http://media.dev-ouvretaferme.org';
		} else {
			return 'https://media.ouvretaferme.org';
		}

	}

};

MediaSetting::$types = [

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

	'association-document' => [
		'class' => 'AssociationDocument',
		'element' => 'association\History',
		'field' => 'document'
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


];

?>
