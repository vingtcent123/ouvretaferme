<?php
/**
 * Assets handling
 *
 */
class Asset {

	/**
	 * JS files imported
	 */
	private static array $js = [];

	/**
	 * JS library files imported
	 */
	private static array $jsLib = [];

	/**
	 * CSS files imported
	 */
	private static array $css = [];

	/**
	 * CSS library files imported
	 */
	private static array $cssLib = [];

	/**
	 * Media version
	 */
	private static ?string $version = NULL;

	/**
	 * Import JS content
	 */
	public static function jsContent(string $js): void {
		self::$js[] = $js;
	}

	/**
	 * Import JS URL
	 */
	public static function jsUrl(string $url, array $arguments = []): AssetElement {
		self::$js[$url] = new AssetElement($url, 'js', $arguments);
		return self::$js[$url];
	}

	/**
	 * Import JS
	 * You can either set an URL (ie : http://www.test.com/jquery.js) or a couple of arguments (packageName, name of JS file) (ie: 'util', 'lime.js')
	 *
	 */
	public static function js(...$arguments): ?AssetElement {

		if(count($arguments) === 1) {

			$path = $arguments[0];

			self::$js[$path] = new AssetElement($path, 'js');

		} else {

			[$packageName, $file] = $arguments;

			$path = self::directory($packageName).'/js/'.$file;

			if(isset(self::$js[$path]) === FALSE) {
				self::$js[$path] = new AssetElement($path, 'js');
			}


		}

		return self::$js[$path];

	}

	/**
	 * Import CSS content
	 */
	public static function cssContent(string $css): void {
		self::$css[] = $css;
	}

	/**
	 * Import CSS URL
	 */
	public static function cssUrl(string $url, array $arguments = []): AssetElement {
		self::$css[$url] = new AssetElement($url, 'css', $arguments);
		return self::$css[$url];
	}

	/**
	 * Import CSS
	 * You can either set an URL (ie : http://www.test.com/jquery.js) or a couple of arguments (packageName, name of CSS file) (ie: 'user', 'user.js')
	 *
	 */
	public static function css(string $packageName, string $file): AssetElement {

		$path = self::directory($packageName).'/css/'.$file;

		if(isset(self::$css[$path]) === FALSE) {
			self::$css[$path] = new AssetElement($path, 'css');
		}

		return self::$css[$path];

	}

	private static $hasGoogleFont = FALSE;

	/**
	 * Import Google Font
	 * You can either set an URL (ie : http://www.test.com/jquery.js) or a couple of arguments (packageName, name of CSS file) (ie: 'user', 'user.js')
	 *
	 */
	public static function googleFont(string $url): void {

		if(self::$hasGoogleFont === FALSE) {
			self::cssUrl('https://fonts.googleapis.com', ['rel' => 'preconnect']);
			self::cssUrl('https://fonts.gstatic.com', ['rel' => 'preconnect', 'crossorigin']);
			self::$hasGoogleFont = TRUE;
		}

		self::cssUrl($url);

	}

	/**
	 * Import Lib
	 *
	 * @param string $packageName Name of the package
	 * @param string file Name of CSS/JS file
	 */
	public static function lib(string $packageName, string $file): ?AssetElement {

		$path = self::directory($packageName).'/lib/'.$file;

		if(substr($file, -3) === '.js') {

			if(isset(self::$jsLib[$path]) === FALSE) {
				self::$jsLib[$path] = new AssetElement($path, 'js');
			}

			return self::$jsLib[$path];

		} else if(substr($file, -4) === '.css') {

			if(isset(self::$cssLib[$path]) === FALSE) {
				self::$cssLib[$path] = new AssetElement($path, 'css');
			}

			return self::$cssLib[$path];

		}

		return NULL;

	}

	/**
	 * Import assets with a HTML structure and empty assets stack
	 *
	 * @return string
	 */
	public static function importHtml(): string {

		$h = '';

		$css = self::getCss();
		$js = self::getJs();

		if(Setting::get('dev\minify')) {

			if($css) {

				$minifyCss = [];

				foreach($css as $key => $cssFile) {

					if(is_string($cssFile)) {
						$h .= $cssFile;
					} else {

						if(
							str_starts_with($key, 'http') === FALSE and
							str_ends_with($key, '.css')
						) {
							$minifyCss[$key] = $cssFile;
						} else {
							$h .= $cssFile;
						}

					}

				}

				$files = \dev\MinifyLib::extractFiles($minifyCss);


				$path = '/minify/'.self::getVersion().'/'.\dev\MinifyLib::buildFilename($files, 'css').'?m='.join(',', $files);

				$h .= self::includeCss($path);

			}

			if($js) {

				$minifyJs = [];

				foreach($js as $key => $jsFile) {

					if(is_string($jsFile)) {
						$h .= $jsFile;
					} else {

						if(
							str_starts_with($key, 'http') === FALSE and
							str_ends_with($key, '.js')
						) {
							$minifyJs[$key] = $jsFile;
						} else {
							$h .= $jsFile;
						}

					}

				}

				$files = \dev\MinifyLib::extractFiles($minifyJs);


				$path = '/minify/'.self::getVersion().'/'.\dev\MinifyLib::buildFilename($files, 'js').'?m='.join(',', $files);

				$h .= self::includeJs($path);

			}

		} else {

			foreach($css as $element) {
				$h .= $element;
			}

			foreach($js as $element) {
				$h .= $element;
			}

		}

		$loaded = [];

		foreach(array_merge($css, $js) as $element) {
			if($element instanceof AssetElement) {
				$loaded[] = $element->getPath();
			}
		}

		$h .= '<script type="text/javascript">';
			$h .= 'Ajax.Asset.loaded = '.json_encode($loaded).';';
			$h .= 'Ajax.Asset.version =  "'.self::getVersion().'";';
		$h .= '</script>';

		return $h;

	}

	/**
	 * Import assets with a JSON structure and empty assets stack
	 *
	 * @return string
	 */
	public static function importJson(): array {

		$json = [
			'version' => self::getVersion(),
			'minify' => \Setting::get('dev\minify'),
			'js' => [],
			'jsCode' => '',
			'css' => [],
			'cssCode' => ''
		];

		foreach(Asset::getJs() as $element) {
			if(is_string($element)) {
				$json['jsCode'] .= preg_replace('/<script( [^>]*)?>(.*)<\/script>/si', '\\2;', $element);
			} else {
				$json['js'][] = $element->getPath();
			}
		}

		foreach(Asset::getCss() as $element) {
			if(is_string($element)) {
				$json['cssCode'] .= preg_replace('/<style( [^>]*)?>(.*)<\/style>/si', '\\2;', $element);
			} else {
				$json['css'][] = $element->getPath();
			}
		}

		return $json;

	}

	/**
	 * Display HTML code to include a CSS file
	 *
	 */
	public static function includeCss(string $path, array $arguments = []): string {
		return '<link type="text/css" rel="stylesheet" href="'.encode($path).'" '.attrs($arguments).'/>'."\n";
	}

	/**
	 * Returns CSS files and empty stack
	 *
	 * @return array
	 */
	public static function getCss(): array {
		$css = array_merge(self::$cssLib, self::$css);
		self::$cssLib = [];
		self::$css = [];
		return $css;
	}

	/**
	 * Display HTML code to include a JS file
	 *
	 * @param string $path JS file
	 * @return string
	 */
	public static function includeJs(string $path, array $arguments = []): string {
		return '<script type="text/javascript" src="'.encode($path).'" '.attrs($arguments).'></script>'."\n";
	}


	/**
	 * Returns JS files and empty stack
	 *
	 * @return array
	 */
	public static function getJs(): array {
		$js = array_merge(self::$jsLib, self::$js);
		self::$jsLib = [];
		self::$js = [];
		return $js;
	}

	/**
	 * Display an image:
	 * - Asset::image([package], [image file]);
	 * - Asset::image([url]);
	 *
	 * @param string $package
	 * @param string/array $file
	 * @param array $attributes Default attributes
	 */
	public static function image(string $package, ?string $file = NULL, array $attributes = []): string {

		// No package provided
		if($file === NULL) {
			$source = $package;
			$version = '';
		} else {
			$source = self::path($package, $file);
			$version = self::getVersion();
		}

		return  '<img src="'.encode($source).$version.'" '.attrs($attributes).'/>';

	}

	/**
	 * Get path of a media (such as package:uri)
	 *
	 * @param string $package
	 * @param string $file
	 * @param string $type
	 *
	 * @return string
	 */
	public static function path(string $package, string $file, string $type = 'image'): string {

		$path = self::directory($package).'/'.$type.'/'.$file."?".self::getVersion();

		return $path;

	}

	/**
	 * Display an icon
	 *
	 * @param string $name
	 * @param array $attributes Additional attributes
	 *
	 * @param string
	 */
	public static function icon(string $name, array $attributes = []): string {

		$class = 'asset-icon asset-icon-'.$name;

		if(isset($attributes['class'])) {
			if(is_array($attributes['class'])) {
				$class .= join(" ", $attributes['class']);
			} else {
				$class .= " ".$attributes['class'];
			}
			unset($attributes['class']);
		}

		$icon = '<svg class="'.$class.'" fill="currentColor" '.attrs($attributes).'>';
		  $icon .= '<use xlink:href="'.self::directory('util').'/lib/bootstrap-icons-1.10.2/bootstrap-icons.svg?2#'.$name.'"/>';
		$icon .= '</svg>';

		return $icon;
	}

	/**
	 * Get media directory
	 *
	 * @param string $package
	 *
	 * @return string
	 */
	protected static function directory(string $package): string {

		$path = '/asset/'.\Package::getApp($package).'/'.$package;

		return $path;
	}

	/**
	 * Sets the reference media version
	 *
	 * @param string $version
	 * @param string $directory
	 */
	public static function setVersion(string $version) {
		self::$version = $version;
	}

	/**
	 * Get media version
	 *
	 * @return int
	 */
	public static function getVersion(): string {
		if(self::$version === NULL) {
			return SERVER('HTTP_X_ASSET_VERSION', 'string', str_replace('.', '', LIME_TIME));
		} else {
			return self::$version;
		}
	}

}

class AssetElement {

	/**
	 * Build the asset
	 *
	 * @param string $path
	 * @param string $type
	 */
	public function __construct(
		protected string $path,
		protected string $type,
		protected array $arguments = []
	) {

	}

	public function getPath(): string {
		return $this->path;
	}

	public function __toString(): string {

		$path = $this->path;

		if(
			Setting::get('dev\minify') === FALSE and
			str_starts_with($path, 'http') === FALSE
		) {
			$path .= '?'.Asset::getVersion();
		}

		switch($this->type) {

			case 'css' :
				return Asset::includeCss($path, $this->arguments);

			case 'js' :
				return Asset::includeJs($path, $this->arguments);

		}

	}

}
?>
