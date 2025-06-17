<?php
namespace storage;

/**
 * Image post-treatment
 */
class BufferLib {

	/**
	 * Optimize all buffered images
	 */
	public static function run() {

		while((int)date('i') < 55) {

			$eBuffer = Buffer::model()
				->select('type', 'basename')
				->get();

			if($eBuffer->empty()) {
				break;
			}

			$type = $eBuffer['type'];
			$basename = $eBuffer['basename'];

			$affected = Buffer::model()
				->whereType($type)
				->whereBasename($basename)
				->delete();

			if($affected > 0) {
	
				$formats = \Setting::get($type)['imageFormat'];

				$path = ServerLib::getAbsolutePath($type, NULL, $basename);
				self::recompress($path);

				foreach($formats as $format) {

					if(self::canRecompress($format)) {

						$path = ServerLib::getAbsolutePath($type, $format, $basename);
						self::recompress($path);

					}

				}

			}

		}

	}

	/**
	 * Recompress a JPG image
	 *
	 * @param string $path
	 */
	public static function recompress(string $path) {

		$command = \Lime::getPath('framework').'/storage/lib/jpeg-recompress ';
		$command .= '-c ';
		$command .= $path.' ';
		$command .= $path;

		ob_start();
		exec($command);
		ob_end_clean();

	}

	/**
	 * Can recompress this format?
	 *
	 * @param $format
	 * @return bool
	 */
	public static function canRecompress($format): bool {

		if(is_array($format)) {
			$format = max($format);
		}

		return ($format === NULL or $format > 1000);

	}

}
?>
