<?php
namespace media;

class LimeObserverLib {

	/**
	 * Build JS configuration file
	 */
	public static function loadConf() {

		$data = 'class ImageConf {'."\n";

		$data .= '	static sizeMax = '.\Setting::get('media\maxImageSize').';'."\n";
		$data .= '	static imageCropRequiredFactor = '.\Setting::get('media\imageCropRequiredFactor').';'."\n";

		$data .= '	static imagesRequiredSize = {'."\n";
		foreach(\Setting::get('media\images') as $type) {

			$size = \Setting::get($type)['imageRequiredSize'];
			$formats = \Setting::get($type)['imageFormat'];

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
			$data .= '			error: "'.(new \media\AlertUi())->getRequiredSize($width, $height).'"'."\n";
			$data .= '		},'."\n";

		}
		$data = substr($data, 0, -2)."\n"; // Trim last comma
		$data .= '	}'."\n";

		$data .= '};'."\n";

		$data .= "\n";

		$data .= 'class ImageMessage {'."\n";

		foreach((new \media\AlertUi())->getJavascript() as $type => $message) {

			$data .= '	static '.$type.' = "'.addcslashes($message, '"').'";'."\n";

		}

		$data = substr($data, 0, -2)."\n"; // Trim last comma

		$data .= '};'."\n";

		$path = \Lime::getPath('ouvretaferme').'/media/asset/js/conf.js';

		file_put_contents($path, $data);

	}


}
?>
