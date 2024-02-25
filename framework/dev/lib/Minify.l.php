<?php
namespace dev;

/**
 * To minify JS and CSS
 *
 * @author Émilie Guth
 */
class MinifyLib {

	/**
	 * Extracts files list according to an asset element array
	 *
	 * @param array $assets
	 * @return array
	 */
	public static function extractFiles(array $assets): array {

		$paths = array_unique(array_keys($assets));
		$files = [];

		foreach($paths as $path) {

			if(substr_count($path, '/') < 3) {
				continue;
			}

			$split = array_filter(explode('/', $path));

			array_shift($split); // asset
			array_shift($split); // app

			$package = array_shift($split);
			$formattedFile = $package.':'.join('/', $split);

			$files[] = substr($formattedFile, 0, strrpos($formattedFile, '.'));

		}

		return $files;

	}

	/**
	 * Constructs the file name according to the list of medias required
	 *
	 * @param $files array treated by extractFiles first
	 *
	 * @return string
	 */
	public static function buildFilename(array $files, string $type): string {

		return md5(implode('|', $files)).'.'.$type;

	}

	public static function clean(): void {

		$directories = glob(\Setting::get('dev\minifyDirectory').'/'.LIME_APP.'/*');

		foreach($directories as $directory) {

			$version = basename($directory);

			if(\Asset::getVersion() !== $version) {
				exec('rm -rf '.$directory);
			}

		}

	}
}

?>