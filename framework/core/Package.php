<?php

/**
 * Package handling
 */
class Package {

	/**
	 * List of packages
	 *
	 * @var array
	 */
	private static $list = [];

	/**
	 * Registered observers
	 *
	 * @var array
	 */
	private static $observers = [];

	/**
	 * Save lists
	 *
	 * @param array $lists
	 */
	public static function setList(array $list) {
		self::$list = $list;
	}

	/**
	 * Returns registered lists
	 *
	 * @return array
	 */
	public static function getList(): array {
		return self::$list;
	}

	/**
	 * Checks if a package exists
	 *
	 * @param string $package
	 * @return bool
	 */
	public static function exists(string $package): bool {
		return (self::$list[$package] ?? NULL) !== NULL;
	}

	/**
	 * Get the app of a package
	 *
	 * @param string $package
	 * @return string
	 */
	public static function getApp(string $package) {
		return self::$list[$package] ?? NULL;
	}

	/**
	 * Get path of a package
	 *
	 * @param string $package
	 */
	public static function getPath(string $package = 'main'): string {
		$app = self::$list[$package];
		return Lime::getPath($app).'/'.$package;
	}

	/**
	 * Save observers
	 *
	 * @param array $observers
	 */
	public static function setObservers(array $observers) {
		self::$observers = $observers;
	}

	/**
	 * Returns registered observers
	 *
	 * @return array
	 */
	public static function getObservers(): array {
		return self::$observers;
	}

	/**
	 * Get the package from a path
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getPackageFromPath(string $path): string {

		// General path
		$path = substr($path, strlen(LIME_DIRECTORY) + 1);
		$path = substr($path, strpos($path, '/') + 1);

		$package = strstr($path, '/', TRUE);

		return $package;

	}

	/**
	 * Get the type from a path (ie: 'ui', 'lib', 'view'...)
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getTypeFromPath(string $path): string {

		$type = substr($path, strlen(LIME_DIRECTORY) + 1);
		$type = substr(strstr($type, '/'), 1); // App
		$type = substr(strstr($type, '/'), 1); // Package
		$type = strstr($type, '/', TRUE); // Path type (ui, lib, ...)

		return $type;

	}

	/**
	 * Get the file from a path (ie: 'ui', 'lib', 'view'...)
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getFileFromPath(string $path): string {

		$file = substr($path, strlen(LIME_DIRECTORY) + 1);
		$file = substr(strstr($file, '/'), 1); // App
		$file = substr(strstr($file, '/'), 1); // Package
		$file = substr(strstr($file, '/'), 1); // Type (ui, lib, ...)
		$file = strstr($file, '.', TRUE); // Extension

		return $file;

	}

	/**
	 * Get the file name from a uri without extension
	 *
	 * @param string $uri The uri (ie: toto/tata)
	 * @param string $type Type of uri (page, view)
	 * @return string|null
	 */
	public static function getFileFromUri(string $uri, string $type) {

		if(strpos($uri, '/') !== FALSE) {

			list($newPackage, $newUri) = explode('/', $uri, 2);

			$app = self::getApp($newPackage);

			if($app !== NULL) {
				$package = $newPackage;
				$file = $newUri;
			} else {
				$package = 'main';
				$file = $uri;
			}

		} else {
			$package = 'main';
			$file = $uri;
		}

		return self::getFile($file, $type, $package);

	}

	/**
	 * Get a path
	 *
	 * @param string $file
	 * @param string $type Type of file (ui, lib, page, view...)
	 * @return string/null $package A path
	 */
	public static function getFile(string $file, string $type, string $package): ?string {

		switch($type) {

			case 'lib' :
				return self::getElement('lib/'.$file.'.l.php', $package);

			case 'ui' :
				return self::getElement('ui/'.$file.'.u.php', $package);

			case 'conf' :
				return self::getElement('conf/'.$file.'.c.php', $package);

			case 'setting' :
				return self::getElement($file.'.c.php', $package);

			case 'module' :
				return self::getElement('module/'.$file.'.m.php', $package);

			case 'element' :
				return self::getElement('module/'.$file.'.e.php', $package);

			case 'observer-lib' :
				return self::getElement('lib/'.$file.'.o.php', $package);

			case 'observer-ui' :
				return self::getElement('ui/'.$file.'.o.php', $package);

			case 'page' :
				return self::getElement('page/'.$file.'.p.php', $package);

			case 'view' :
				return self::getElement('view/'.$file.'.v.php', $package);

			case 'test' :
				return self::getElement('test/'.$file.'.t.php', $package);

			case 'template' :
				return self::getElement('view/'.$file.'.php', $package);

			default :
				throw new Exception('Type \''.$type.'\' is not supported');

		}

	}

	/**
	 * Get a translation path
	 *
	 * @param string $file
	 * @param string $lang Translation lang
	 * @param string $package Package name
	 */
	public static function getLanguage(string $file, ?string $lang = NULL, string $package = LIME_APP): string {

		if($lang === NULL) {
			$lang = L::getLang();
		}

		$file = $package.'/lang/'.$lang.'/'.$file;

		return self::getElement($lang.'/'.$file, $package);

	}

	private static $files = [];
	private static $lastFile;

	/**
	 * Get the requested file if it exists
	 *
	 * @param string $file File name (ie: view/index.v.php)
	 * @param string $package Package name
	 */
	protected static function getElement(string $file, string $package): ?string {

		if(strpos($file, chr(0)) !== FALSE) {
			throw new Exception("Requested file '".$file."' contains NULL character");
		}

		if(isset(self::$list[$package]) === FALSE) {
			return NULL;
		}

		$app = self::$list[$package];

		$path = Lime::getPath($app).'/'.$package.'/'.$file;

		if(isset(self::$files[$path]) === FALSE) {
			self::$files[$path] = is_file($path);
		}

		self::$lastFile = $path;

		$isFile = self::$files[$path];

		if($isFile) {
			return $path;
		} else {
			return NULL;
		}

	}

	/**
	 * Get last tested files
	 */
	public static function getLastFile() {
		return self::$lastFile;
	}

}

/**
 * Trait for notifiable objects
 */
trait Notifiable {

	/**
	 * Call a method named after the event from all the attached observers
	 *
	 * @param string $event The name of the event, must be camelCased
	 * @param array $data Arguments
	 */
	private static function notify(string $event, &...$data): array {

		$observers = Package::getObservers();

		if(strpos($event, '\\') !== FALSE) {
			list($packageEvent, $event) = explode('\\', $event);
		} else if(strpos(__CLASS__, '\\') === FALSE) {
			$packageEvent = 'lime';
		} else {
			$packageEvent = strstr(__CLASS__, '\\', TRUE);
		}

		if(
			substr(__CLASS__, -2) === 'Ui' or
			substr(__CLASS__, -4) === 'Template'
		) {
			$type = 'ui';
		} else {
			$type = 'lib';
		}

		$output = [];

		if(isset($observers[$type][$packageEvent][$event])) {

			foreach($observers[$type][$packageEvent][$event] as $packageObserver) {

				switch($type) {

					case 'lib' :
						$class = '\\'.$packageObserver.'\\'.ucfirst($packageEvent).'ObserverLib';
						break;

					case 'ui' :
						$class = '\\'.$packageObserver.'\\'.ucfirst($packageEvent).'ObserverUi';
						break;

				}

				try {

					$output[] = $class::$event(...$data);

				} catch(Error $e) {

				} catch(ObserverOutput $e) {
					return $e->getOutput();
				}

			}

		}

		return $output;

	}

}

/**
 * Final observer
 */
class ObserverFinal extends Exception {

}

/**
 * Class ObserverOutput
 */
class ObserverOutput extends Exception {

	private $output;

	public function __construct($output) {

		$this->output = $output;

		return parent::__construct();

	}

	public function getOutput() {
		return $this->output;
	}

}

class Settings {

	/**
	 * Throws a DisabledPage exception if the given feature is disabled
	 *
	 * @throws DisabledPage If feature is disabled
	 */
	public static function checkFeature(bool $feature): void {

		// Check class feature
		if($feature === FALSE) {
			throw new DisabledPage('Feature disabled');
		}

	}
}

?>
