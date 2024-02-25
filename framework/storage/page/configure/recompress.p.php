<?php
/**
 * Resize original images according to imagesRequiredSize
 *
 */
(new Page())
	->cli('index', function($data) {

		$type = GET('type');

		if(\storage\ImageLib::checkType($type) === FALSE) {
			throw new LineAction('Error: Invalid type='.$type.'');
		}

		if(get_exists('size')) {

			$possibleFormats = \Setting::get('storage\\'.$type)['imageFormat'];

			$size = GET('size');

			if($size === 'original') {
				$format = NULL;
			} else if(array_key_exists($size, $possibleFormats)) {
				$format = $possibleFormats[$size];
			} else {
				throw new LineAction('Error: Invalid size='.$size.' (expected a valid string - see setting \'imageFormat\')');
			}

			$formats = [$format];

		} else {

			echo 'No format=? provided, take all of them...'."\n";

			$formats = \Setting::get('storage\\'.$type)['imageFormat'];

			foreach($formats as $key => $format) {

				if(\storage\BufferLib::canRecompress($format) === FALSE) {
					unset($formats[$key]);
				}

			}

			$formats[] = NULL;

		}

		echo "Add:\n";
		echo "* Type: ".$type."\n";
		echo "* Formats: \n";

		foreach($formats as $format) {
			echo ' - ';
			if(is_int($format)) {
				echo $format;
			} else if($format === NULL) {
				echo 'original';
			} else {
				echo implode(' x ', $format);
			}
			echo "\n";
		}

		for($i = 5; $i >= 0; $i--) {
			echo $i."\n";
			sleep(1);
		}

		\storage\ServerLib::browse($type, function($file) use($type, $formats) {

			$basename = $file->getFileName();

			foreach($formats as $format) {

				$path = \storage\ServerLib::getAbsolutePath($type, $format, $basename);
				\storage\BufferLib::recompress($path);

			}

			echo '.';

		});


	});

?>
