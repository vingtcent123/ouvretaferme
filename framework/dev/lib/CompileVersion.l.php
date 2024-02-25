<?php
namespace dev;

/**
 * Creation version numbers for JS / CSS / Image files
 */
class CompileVersionLib {

	/**
	 * Create version number for images
	 *
	 * @param string $app
	 */
	public static function image(string $app): array {

		if(Feature::get('compileImageVersion') === FALSE) {
			return [];
		}

		$packages = (new \ReflectionApp($app))->getPackages();
		$versions = [];

		foreach($packages as $package) {

			$checksums = [];

			$package->browse('media/image', NULL, function($file) use (&$checksums) {

				$path = $file->getPathname();

				// Use the last modification time to identify the version of a file
				$checksums[$path] = $file->getMTime();

			});

			if($checksums) {
				ksort($checksums);
				$versions[$package->getPackageName()] = crc32(implode('', $checksums));
			}

		}

		return $versions;

	}

	/**
	 * Create version number for CSS
	 *
	 * @param string $app
	 */
	public static function css(string $app = LIME_APP): array {
		return self::code($app, 'css');
	}

	/**
	 * Create version number for JS
	 *
	 * @param string $app
	 */
	public static function js(string $app = LIME_APP): array {
		return self::code($app, 'js');
	}

	/**
	 * Generic method to create version number for code files
	 *
	 * @param type $app
	 * @param type $type
	 */
	protected static function code(string $app, string $type): array {

		if(Feature::get('compileCodeVersion') === FALSE) {
			return [];
		}

		$packages = (new \ReflectionApp($app))->getPackages();
		$versions = [];

		foreach($packages as $package) {

			$checksums = [];

			$package->browse('media/'.$type, '.'.$type, function($file) use (&$checksums, $type) {

				$path = $file->getPathname();

				// Remove minify directory
				if(strpos($path, '/'.$type.'/minify/') === FALSE) {
					// Use the MD5 checksum of identify the version of a file
					$checksums[$path] = md5_file($path);
				}

			});

			if($checksums) {
				ksort($checksums);
				$versions[$package->getPackageName()] = crc32(implode('', $checksums));
			}

		}

		return $versions;

	}

}
?>
