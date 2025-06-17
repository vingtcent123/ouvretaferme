<?php
Setting::register('storage', [

	'images' => ['user-vignette', 'editor'],

	'user-vignette' => [
		'class' => 'UserVignette',
		'element' => 'user\User',
		'field' => 'vignette',
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
	],

	'editor' => [
		'class' => 'Editor',
		'element' => NULL,
		'field' => NULL,
		'imageFormat' => [
			'xs' => 100,
			's' => 500,
			'm' => 750,
			'l' => 1000
		],
		'imageCropReference' => (int)(1000 / 1.1),
		'imageResizeReference' => ['l', 's'],
		'imageOutputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF],
		'imageMaxLength' => 5000,
		'imageMinPixels' => 10000000,
		'imageRequiredSize' => 50,
	],

	'imageCropRequiredFactor' => 1.1,
	'imageResizeRequiredFactor' => 1.15,

	'defaultQuality' => [
		IMAGETYPE_JPEG => 85,
		IMAGETYPE_PNG => 9,
		IMAGETYPE_GIF => NULL
	],

	'imagesInputType' => [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP],

	'basePath' => '/var/www/storage',

]);
?>
