<?php
/**
 * Reflection for packages
 */
class ReflectionPackage {

	/**
	 * Package name
	 *
	 * @var string
	 */
	protected $packageName;

	/**
	 * Package application
	 *
	 * @var ReflectionApp
	 */
	protected $app;

	/**
	 * Path of the package
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Langs of the package
	 *
	 * @var array
	 */
	protected $langs;

	/**
	 * Create a new reflection instance for an package
	 *
	 * @param string $packageName Package name
	 * @param ReflectionApp Package application
	 */
	public function __construct(string $packageName, $app) {

		$this->packageName = (string)$packageName;

		if($app instanceof ReflectionApp) {
			$this->app = $app;
		} else {
			$this->app = new ReflectionApp($app);
		}

		$this->path = LIME_DIRECTORY.'/'.$this->app->getAppName().'/'.$this->packageName;

	}

	/**
	 * Checks is the package exists
	 *
	 * @return bool
	 */
	public function exists(): bool {
		return (
			$this->packageName !== '' and
			$this->app->exists() and
			is_dir($this->path)
		);
	}

	/**
	 * Get the path of the package
	 *
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}


	/**
	 * Return package name
	 *
	 * @return string
	 */
	public function getPackageName(): string {
		return $this->packageName;
	}

	/**
	 * Return package application
	 *
	 * @return ReflectionApp
	 */
	public function getApp(): ReflectionApp {
		return $this->app;
	}


	/**
	 * Get all langs found in the package
	 *
	 * @return array
	 */
	public function getLangs(): array {

		if($this->langs === NULL) {

			$directory = $this->path.'/lang/';

			if(is_dir($directory)) {

				$this->langs = array_filter(scandir($directory), function($lang) {
					return (strlen($lang) === 5 and strpos($lang, '_') === 2);
				});

				$this->langs = array_merge($this->langs);

			} else {
				$this->langs = [];
			}

		}

		return $this->langs;

	}

	/**
	 * Get dependencies of a package
	 *
	 */
	public function getDependencies(): array {

		$dependencies = [];

		$files = [];
		exec('find '.$this->path.'/page/ '.$this->path.'/lib/ '.$this->path.'/view/ '.$this->path.'/conf/ '.$this->path.'/ui/ -name "*.php" 2> /dev/null', $files);

		foreach($files as $file) {

			$content = file_get_contents($file);
			$tokens = token_get_all($content);

			foreach($tokens as $key => $value) {

				// Extract package name
				if(
					is_array($value) and $value[0] === T_NS_SEPARATOR and
					is_array($tokens[$key - 1]) and $tokens[$key - 1][0] === T_STRING
				) {
					$dependency = $tokens[$key - 1][1];
					if($dependency !== $this->packageName) {
						$dependencies[] = $tokens[$key - 1][1];
					}
				}

			}

		}

		return array_unique($dependencies);

	}


	/**
	 * Browse recursively files in the given directory
	 *
	 * @param mixed $directories Browse this directory in the application (ie: media/)
	 * @param mixed $extensions Filter files with this extension (set NULL to disable this feature)
	 * @param callable $callback Callback function for each file
	 */
	public function browse($directories, $extensions, callable $callback) {

		$directories = (array)$directories;
		$extensions = (array)$extensions;

		foreach($directories as $key => $directory) {

			$directory = $this->path.'/'.$directory;
			$extension = $extensions[$key];

			if(is_dir($directory)) {

				$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);

				foreach($files as $file) {

					$path = $file->getPathname();

					if($extension === NULL or substr($path, - strlen($extension)) === $extension) {
						$callback($file);
					}

				}

			}

		}

	}

}
?>
