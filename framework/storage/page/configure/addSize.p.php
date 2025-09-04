<?php
/**
 * Add a new size for an image
 *
 */
new Page()
	->cli('index', function($data) {

		$type = GET('type');

		if(\storage\ImageLib::checkType($type) === FALSE) {
			throw new LineAction('Error: Invalid type='.$type.'');
		}

		$sizeString = GET('size');
		$sizeInt = \storage\StorageSetting::$types['imageFormat'][$sizeString] ?? NULL;

		if($sizeInt === NULL) {
			throw new LineAction('Error: Invalid size='.$sizeString.' (see setting \'imageFormat\')');
		}

		echo "Add:\n";
		echo "* Type: ".$type."\n";
		echo "* Format: ".$sizeString."\n";

		for($i = 5; $i >= 0; $i--) {
			echo $i."\n";
			sleep(1);
		}


		\storage\ServerLib::browse($type, function($file) use($type, $sizeInt, $sizeString) {

			$fileSource = $file->getPathName();
			$typeSource = exif_imagetype($fileSource);

			$pathDestination = \storage\ServerLib::getPath($type, $sizeInt, $file->getFileName());
			$dirDestination = \storage\DriverLib::directory().'/'.dirname($pathDestination);

			if(is_dir($dirDestination) === FALSE) {
				mkdir($dirDestination, 0777, TRUE);
			}

			$pathSource = substr($fileSource, strlen(\storage\DriverLib::directory()) + 1);

			$bounds = \media\MediaSetting::$mediaDriver->getMetadata($pathSource)['crop'] ?? NULL;

			$resource = new \Imagick($fileSource);

			if($bounds) {
				\storage\ImageLib::extractImagePortion($bounds, $resource, $type);
			}

			\storage\ImageLib::resize($sizeInt, $resource, \storage\StorageSetting::$types['imageFormatConstraint'] ?? NULL);

			\storage\ServerLib::buildImage($type, $sizeString, $resource, $pathDestination, $typeSource);

			/*

			// Pour remplacement du format de base
			$m = $fileSource.'.txt';

			if(is_file($m)) {
				$c = json_decode(file_get_contents($m), TRUE);
				$c['width'] = $resource->getImageWidth();
				$c['height'] = $resource->getImageHeight();
				file_put_contents($m, json_encode($c));
			}*/

			echo '.';

		});


	});

?>
