<?php
namespace dev;

/**
 * Run tests
 */
class TestLib {

	/**
	 * Run tests
	 *
	 * @param \ReflectionPackage $package
	 * @param string $selectedFile Selected file or * for all files
	 */
	public static function run(\ReflectionPackage $package, string $selectedFile) {

		$package->browse('test/', '.t.php', function($file) use($package, $selectedFile) {

			$basename = substr($file->getBasename(), 0, -6);

			// Filter selected files
			if($selectedFile !== '*' and $basename !== $selectedFile) {
				return;
			}

			require_once $file->getPathname();

			$class = '\\'.$package->getPackageName().'\\'.$basename.'Test';

			try {

				$reflection = new \ReflectionClass($class);

				if($reflection->isAbstract()) {
					return;
				}

				$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

				$instance = $reflection->newInstance();
				$instance->init();

				foreach($methods as $method) {

					$methodName = $method->getName();

					if(substr($methodName, 0, 4) === 'test') {

						try {

							$instance->setUp();
							$instance->$methodName();
							$instance->tearDown();

						} catch(Exception $e) {
							echo "\033[31mERROR: ".$e->getMessage()."\033[00m".PHP_EOL;
						}

					}
				}

				$instance->finalize();

				echo $instance->show();

			} catch(Exception $e) {
				echo "\033[31mCRITICAL ERROR: ".$e->getMessage()."\033[00m".PHP_EOL;
			}

		});

	}

}
?>
