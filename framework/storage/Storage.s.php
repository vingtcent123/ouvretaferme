<?php
namespace storage;

class StorageSetting extends \media\MediaSetting {

}

StorageSetting::$images = array_unique(['user-vignette', 'editor'] + \media\MediaSetting::$images);

StorageSetting::$types = [
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
		'imageCropShape' => 'circle',
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
		]
	] + \media\MediaSetting::$types;

?>
