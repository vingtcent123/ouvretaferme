<?php
/**
 * Reflection for applications
 */
class ReflectionApp {

	/**
	 * Name of the application
	 *
	 * @var string
	 */
	protected $appName;

	/**
	 * Path of the application
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new reflection instance for an application
	 *
	 * @param string $appName App name
	 */
	public function __construct(string $appName) {
		$this->appName = (string)$appName;
		$this->path = Lime::getPath($this->appName);
	}

	/**
	 * Checks is the app exists
	 *
	 * @return bool
	 */
	public function exists(): bool {
		return (
			$this->appName !== '' and
			is_dir($this->path)
		);
	}

	/**
	 * Get the path of the application
	 *
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * Return app name
	 *
	 * @return string
	 */
	public function getAppName(): string {
		return $this->appName;
	}

	/**
	 * Get packages of the application
	 *
	 * @return array
	 */
	public function getPackages(): array {

		$packages = [];

		$paths = glob($this->path.'/*');

		foreach($paths as $path) {

			$packageName = basename($path);

			if(
				is_dir($path) and (
					$this->appName !== 'lime' or
					($packageName !== 'core' and $packageName !== 'doc')
				)
			) {
				$packages[] = new ReflectionPackage($packageName, $this);
			}

		}

		return $packages;

	}

}
?>
