<?php
namespace storage;

/**
 * Image handling
 */
class ImageLib {

	/**
	 * Returns the base path to store the medias
	 *
	 */
	public static function getBasePath(): string {

		if(LIME_ENV === 'dev') {
			return '/var/www/storage';
		}

		return '/var/www/storage';

	}

	/**
	 * Checks the given type depending on supported types
	 *
	 * @param int $type
	 *
	 * @return boolean
	 */
	public static function checkType(string $type): bool {

		return in_array($type, \Setting::get('storage\images'));

	}

	/**
	 * Checks if the given file at the filename is an image
	 *
	 * @param string $filename
	 *
	 * @return boolean
	 */
	public static function isImage(string $filename): bool {

		if(in_array(exif_imagetype($filename), \Setting::get('imagesInputType')) === FALSE) {
			return FALSE;
		}

		return TRUE;

	}

	/**
	 * Rotates an image if it needs a rotation
	 *
	 * @param string $base64
	 * @param Imagick $resource
	 * @return int $rotation
	 */
	public static function rotateImage(\Imagick $resource, &$rotation = NULL) {

		$rotation = 0;

		switch($resource->getImageOrientation()) {

			case \Imagick::ORIENTATION_TOPLEFT:
				break;

			case \Imagick::ORIENTATION_TOPRIGHT:
				$resource->flopImage();
				break;

			case \Imagick::ORIENTATION_BOTTOMRIGHT:
				$resource->rotateImage('white', 180);
				break;

			case \Imagick::ORIENTATION_BOTTOMLEFT:
				$resource->flopImage();
				$resource->rotateImage('white', 180);
				break;

			case \Imagick::ORIENTATION_LEFTTOP:
				$resource->flopImage();
				$resource->rotateImage('white', -90);
				break;

			case \Imagick::ORIENTATION_RIGHTTOP:
				$resource->rotateImage('white', 90);
				break;

			case \Imagick::ORIENTATION_RIGHTBOTTOM:
				$resource->flopImage();
				$resource->rotateImage('white', 90);
				break;

			case \Imagick::ORIENTATION_LEFTBOTTOM:
				$resource->rotateImage('white', -90);
				break;
		}

		$resource->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);

	}

	/**
	 * Flattens a PNG file to a JPG format
	 *
	 * @param Imagick $resource
	 */
	public static function flatten(\Imagick $resource) {

		$resource->setImageBackgroundColor('white');
		$resource->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);

		$resource = $resource->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);

	}

	/**
	 * Resizes an image according to the given format
	 *
	 * @param mixed $format
	 * @param Imagick $resource
	 * @param string $constraint
	 * @param int $minPixels
	 * @return bool
	 */
	public static function resize($format, \Imagick $resource, string $constraint = NULL, int $minPixels = NULL): bool {

		$widthSource = $resource->getImageWidth();
		$heightSource = $resource->getImageHeight();

		$result = FALSE;

		// Only the largest side is specified
		if(is_int($format)) {

			if($constraint === NULL) {

				$width = $format;
				$height = $format;

				if($widthSource < $heightSource) {
					$heightNew = $height;
					$widthNew = (int)($widthSource * $width / $heightSource);
				} else {
					$widthNew = $width;
					$heightNew = (int)($heightSource * $height / $widthSource);
				}

			} else if($constraint === 'width') {

				$widthNew = $format;
				$heightNew = (int)($heightSource * $widthNew / $widthSource);

			} else {

				$heightNew = $format;
				$widthNew = (int)($widthSource * $heightNew / $heightSource);

			}

			// We need to change the new dimensions so the min pixels is respected
			$currentPixels = $widthNew * $heightNew;
			if($minPixels !== NULL and $currentPixels < $minPixels) {
				$heightNew = pow($minPixels / $currentPixels, 0.5) * $heightNew;
				$widthNew = pow($minPixels / $currentPixels, 0.5) * $widthNew;
			}

		}
		// Format has a mandatory size
		else {

			list($width, $height) = $format;

			$widthNew = $width;
			$heightNew = $height;

			$ratioSource = $widthSource / $heightSource;
			$ratio = $width / $height;

			// Image too large
			if($ratioSource / $ratio > 1.02) {

				$resource->setImagePage(0, 0, 0, 0);

				$cut = (1 - $ratio / $ratioSource) * $widthSource;
				$resource->cropImage($widthSource - $cut, $heightSource, (int)($cut / 2), 0);

				$result = TRUE;

			}
			// Image too high
			else if($ratioSource / $ratio < 0.98) {

				$resource->setImagePage(0, 0, 0, 0);

				$cut = (1 - $ratioSource / $ratio) * $heightSource;
				$resource->cropImage($widthSource, $heightSource - $cut, 0, (int)($cut / 2));

				$result = TRUE;

			}

		}

		if(
			$widthNew < $widthSource or
			$heightNew < $heightSource
		) {

			$resource->scaleImage($widthNew, $heightNew);

			$result = TRUE;

		}

		return $result;

	}

	/**
	 * Extracts a portion from the given information
	 *
	 * @param array $image Information about the image
	 * @param \Imagick $resource
	 * @param string $type type of image
	 *
	 * @return bool
	 */
	public static function extractImagePortion(array $image, \Imagick $resource, string $type): bool {

		// Check consistency
		if(
			$image['top'] < 0 or $image['left'] < 0 or
			$image['width'] < 0 or $image['height'] < 0 or
			$image['top'] + $image['height'] > 100 or $image['left'] + $image['width'] > 100
		) {
			return FALSE;
		}

		$widthOriginal = $resource->getImageWidth();
		$heightOriginal = $resource->getImageHeight();

		$sizePreview = last(\Setting::get($type)['imageFormat']);

		if(is_array($sizePreview)) {
			list($widthPreview, $heightPreview) = $sizePreview;
		} else {
			$widthPreview = $widthOriginal;
			$heightPreview = $heightOriginal;
		}

		$leftCropped = (int)($image['left'] / 100 * $widthOriginal); // position X sur l'image originale
		$topCropped = (int)($image['top'] / 100 * $heightOriginal); // position Y sur l'image originale

		$widthCropped = (int)($image['width'] / 100 * $widthOriginal);
		$heightCropped = (int)($widthCropped * ($heightPreview / $widthPreview));

		// Fix wrong percentages
		if($heightCropped > $heightOriginal) {

			$ratio = $heightOriginal / $heightCropped;

			$heightCropped = $heightOriginal;
			$widthCropped = (int)($widthCropped * $ratio);

		}

		$resource->setImagePage(0, 0, 0, 0);
		$resource->cropImage($widthCropped, $heightCropped, $leftCropped, $topCropped);

		return TRUE;

	}

	/**
	 * Returns the compression level according to the filename
	 *
	 * @param string $type
	 * @param int $fileType
	 * @param string $format
	 * @return int
	 */
	public static function getCompression(string $type, int $fileType, string $format = NULL) {

		$settings = \Setting::get($type);

		if($format === NULL) {
			return \Setting::get('storage\defaultQuality')[$fileType];
		}

		if(isset($settings['imageQuality'][$format])) {

			$qualityByFormat = $settings['imageQuality'][$format];

			if(is_array($qualityByFormat)) {
				return $qualityByFormat[$fileType] ?? \Setting::get('storage\defaultQuality')[$fileType];
			} else if(is_int($qualityByFormat)) {
				return $qualityByFormat;
			}

		}

		return \Setting::get('storage\defaultQuality')[$fileType];

	}

}
?>
