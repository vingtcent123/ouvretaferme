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
	 * Packages that have already been loaded
	 *
	 * @var array
	 */
	private static $loadedPackages = [];

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
	 * Load config.php file for the given package
	 *
	 * @param string $package
	 */
	public static function load(string $package) {

		if(isset(self::$loadedPackages[$package])) {
			return;
		}

		$path = self::getElement($package.'.c.php', $package);

		if($path !== NULL) {
			require_once $path;
		}

		self::$loadedPackages[$package] = TRUE;

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
	public static function getLanguage(string $file, string $lang = NULL, string $package = LIME_APP): string {

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
			throw new Exception("Package '".$package."' does not exist");
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

				} catch(DisabledFeature $e) {

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

/**
 * Handle packages settings
 */
class Setting {

	/**
	 * Registered settings
	 *
	 * @var array
	 */
	private static $settings = [];

	/**
	 * Register a list of settings for a package
	 *
	 * @param string $package Package name
	 * @param array $settings Settings list
	 */
	public static function register(string $package, array $settings): void {

		if(isset(self::$settings[$package]) === FALSE) {
			self::$settings[$package] = [];
		}

		self::$settings[$package] += $settings;

	}

	/**
	 * Copy settings of a package to another one
	 *
	 */
	public static function copy(string $from, string $to): void {
		self::$settings[$to] = self::$settings[$from];
	}

	/**
	 * Change status of a setting
	 *
	 * @param string $setting Setting name [namespace]\[constant] (ie: user\package)
	 * @param mixed $value New value
	 */
	public static function set(string $setting, $value): void {

		if(strpos($setting, '\\') === FALSE) {
			$package = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
			$name = $setting;
		} else {
			list($package, $name) = explode('\\', $setting, 2);
		}

		self::$settings[$package][$name] = $value;

	}

	/**
	 * Adds data to an existing setting
	 *
	 * @param string $setting Setting name [namespace]\[constant] (ie: user\package)
	 * @param array $valueAdded
	 */
	public static function add(string $setting, array $valueAdded): void {

		if(strpos($setting, '\\') === FALSE) {
			$package = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
			$name = $setting;
		} else {
			list($package, $name) = explode('\\', $setting, 2);
		}

		self::$settings[$package][$name] += $valueAdded;

	}

	/**
	 * Return the value of the given setting
	 *
	 * @param string $setting
	 * @return bool
	 */
	public static function get(string $setting): mixed {

		if(strpos($setting, '\\') === FALSE) {
			$package = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
			$name = $setting;
		} else {
			list($package, $name) = explode('\\', $setting, 2);
		}

		if(LIME_ENV !== 'dev' and \Package::exists($package) === FALSE) {
			return FALSE;
		}

		Package::load($package);

		if(array_key_exists($name, self::$settings[$package]) === FALSE) {
			throw new Exception('Setting '.$package.'\\'.$name.' does not exist');
		}

		if(is_closure(self::$settings[$package][$name])) {
			$callable = self::$settings[$package][$name];
			self::$settings[$package][$name] = $callable();
		}

		return self::$settings[$package][$name];

	}

}

/**
 * Handle privileges
 */
class Privilege {

	/**
	 * Registered privileges
	 *
	 * @var array
	 */
	private static $privileges = [];

	/**
	 * Register a list of privileges for a package
	 *
	 * @param string $package Package name
	 * @param array $privileges Privileges list
	 * @param array $override Override existing privileges
	 */
	public static function register(string $package, array $privileges, bool $override = FALSE): void {

		if(isset(self::$privileges[$package]) === FALSE) {
			self::$privileges[$package] = [];
		}

		if($override) {
			self::$privileges[$package] = $privileges + self::$privileges[$package];
		} else {
			self::$privileges[$package] += $privileges;
		}

	}

	/**
	 * Checks if a privilege is registered
	 *
	 * @param string $privilege
	 * @param ...$values
	 * @return bool
	 */
	public static function can(string $privilege, ...$values): bool {

		if(strpos($privilege, '\\') === FALSE) {
			$package = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
			$name = $privilege;
		} else {
			list($package, $name) = explode('\\', $privilege, 2);
		}

		Package::load($package);

		if(isset(self::$privileges[$package][$name]) === FALSE) {
			throw new Exception('Privilege '.$package.'\\'.$name.' does not exist');
		}

		if(is_closure(self::$privileges[$package][$name])) {
			$callable = self::$privileges[$package][$name];
			self::$privileges[$package][$name] = $callable(...$values);
		}

		return self::$privileges[$package][$name];

	}


	/**
	 * Throws a DisabledPage exception if the given privilege is not registered
	 *
	 * @param string $privilege Privilege name (ie : paper\admin)
	 * @throws DisabledPage If privilege is not registered
	 */
	public static function check(string $privilege): void {

		if(strpos($privilege, '\\') === FALSE) {
			$privilege = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']).'\\'.$privilege;
		}

		// Check class privilege
		if(self::can($privilege) === FALSE) {
			throw new DisabledPage('Privilege '.$privilege);
		}

	}

	/**
	 * Change status of a privilege
	 *
	 * @param string $privilege Privilege name [namespace]\[constant] (ie: user\admin)
	 * @param bool $status New status
	 */
	public static function set(string $privilege, bool $status): void {

		if(strpos($privilege, '\\') === FALSE) {
			$package = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
			$name = $privilege;
		} else {
			list($package, $name) = explode('\\', $privilege, 2);
		}

		self::$privileges[$package][$name] = (bool)$status;

	}

}

/**
 * Handle packages features
 */
class Feature {

	/**
	 * Registered features
	 *
	 * @var array
	 */
	private static $features = [];


	/**
	 * Register a list of features for a package
	 *
	 * @param string $package Package name
	 * @param array $features
	 */
	public static function register(string $package, array $features): void {

		if(isset(self::$features[$package]) === FALSE) {
			self::$features[$package] = [];
		}

		self::$features[$package] += $features;

	}

	/**
	 * Change status of a feature
	 *
	 * @param string $feature Feature name [namespace]\[constant] (ie: user\package)
	 * @param bool $status New status
	 */
	public static function set(string $feature, bool $status): void {

		if(strpos($feature, '\\') === FALSE) {
			$package = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
			$name = $feature;
		} else {
			list($package, $name) = explode('\\', $feature, 2);
		}

		self::$features[$package][$name] = (bool)$status;
	}


	/**
	 * Return status of a feature
	 *
	 * @param string $feature Feature name [namespace]\[constant] (ie: user\package)
	 * @param ...$values
	 */
	public static function get(string $feature, ...$values): mixed {

		if(strpos($feature, '\\') === FALSE) {
			$package = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']);
			$name = $feature;
		} else {
			list($package, $name) = explode('\\', $feature, 2);
		}

		if(LIME_ENV !== 'dev' and \Package::exists($package) === FALSE) {
			return FALSE;
		}

		Package::load($package);

		if(isset(self::$features[$package][$name]) === FALSE) {
			throw new Exception('Feature '.$package.'\\'.$name.' does not exist');
		}

		if(is_closure(self::$features[$package][$name])) {
			$callable = self::$features[$package][$name];
			self::$features[$package][$name] = $callable(...$values);
		}

		return self::$features[$package][$name];

	}


	/**
	 * Throws a DisabledPage exception if the given feature is disabled
	 *
	 * @param string $feature Feature name (ie : user\admin)
	 * @throws DisabledPage If feature is disabled
	 */
	public static function check(string $feature): void {

		if(strpos($feature, '\\') === FALSE) {
			$feature = Package::getPackageFromPath(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']).'\\'.$feature;
		}

		// Check class feature
		if(self::get($feature) === FALSE) {
			throw new DisabledPage('Feature '.$feature);
		}

	}

}
?>
