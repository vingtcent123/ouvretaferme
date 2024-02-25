<?php
namespace dev;

/**
 * Compilation
 * - create versioning for JS, CSS & image files
 * - minify JS & CSS files
 */
class CompileLib {

	/**
	 * Compile the given app
	 *
	 * @param string $app
	 */
	public static function run(string $app = LIME_APP) {

		$versions = [
			'css' => CompileVersionLib::css($app),
			'js' => CompileVersionLib::js($app),
			'image' => CompileVersionLib::image($app),
		];

		var_dump($versions);

	}

}
?>
