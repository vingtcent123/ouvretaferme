<?php

/**
 * Run test pages
 *
 */
(new Page())
	->cli('index', function($data) {

		$selectedFile = GET('file', 'string', '*');
		$selectedPackage = GET('package', 'string', '*');

		if($selectedPackage === '*') {

			$packages = [];

			foreach(Package::getList() as $package => $app) {
				$packages[] = new ReflectionPackage($package, $app);
			}

		} else {
			$packages = [new ReflectionPackage($selectedPackage, Package::getApp($selectedPackage))];
		}

		foreach($packages as $package) {

			if($package->exists()) {

				\dev\TestLib::run($package, $selectedFile);

			} else {
				throw new LineAction("Error: Package '".$package->getPackageName()."' does not exist");
			}

		}

	})
	/**
	 * Display help
	 */
	->cli('help', function($data) {

		echo "Usage: dev/test package=[package name] file=[file name] ...\n";
		echo "\n";
		echo "DESCRIPTION\n";
		echo "	Run test pages.\n";
		echo "\n";
		echo "OPTIONS\n";
		echo "	package=[package name]\n";
		echo "		The name of the package (use * for all packages).\n";
		echo "	file=[file name]\n";
		echo "		Limit the test to the specified file.\n";

	});
?>
