<?php
namespace media;

class LimeObserverLib {

	/**
	 * Build JS configuration file
	 */
	public static function loadConf() {

		$data = 'class ImageConf {'."\n";

		$data .= '	static sizeMax = '.MediaSetting::MAX_IMAGE_SIZE.';'."\n";
		$data .= '	static imageCropRequiredFactor = '.\storage\StorageSetting::IMAGE_CROP_REQUIRED_FACTOR.';'."\n";

		$data .= '	static imagesRequiredSize = {'."\n";
		foreach(MediaSetting::$images as $type) {

			$settings = MediaSetting::$types[$type];

			if(empty($settings['imageFormat'])) {
				continue;
			}

			$size = $settings['imageRequiredSize'];
			$formats = $settings['imageFormat'];

			if(is_int($size)) {

				$width = $height = $size;

			} else {

				$format = $formats[$size];

				if(is_array($format)) {
					list($width, $height) = $format;
				} else {
					$width = $height = $format;
				}

			}

			$data .= '		\''.$type.'\': {'."\n";
			$data .= '			width: '.$width.','."\n";
			$data .= '			height: '.$height.','."\n";
			$data .= '			error: "'.new \media\AlertUi()->getRequiredSize($width, $height).'"'."\n";
			$data .= '		},'."\n";

		}
		$data = substr($data, 0, -2)."\n"; // Trim last comma
		$data .= '	}'."\n";

		$data .= '};'."\n";

		$data .= "\n";

		$data .= 'class ImageMessage {'."\n";

		foreach(new \media\AlertUi()->getJavascript() as $type => $message) {

			$data .= '	static '.$type.' = "'.addcslashes($message, '"').'";'."\n";

		}

		$data = substr($data, 0, -2)."\n"; // Trim last comma

		$data .= '};'."\n";

		$path = \Lime::getPath('base').'/media/asset/js/conf.js';

		file_put_contents($path, $data);

	}


}
?>
