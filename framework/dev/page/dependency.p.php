<?php

/**
 * Show dependencies for a package
 */
(new Page())
	->cli('index', function($data) {

		$packages = (new ReflectionApp(LIME_APP))->getPackages();

		foreach($packages as $package) {

			echo '--- '.$package->getPackageName().' ---'."\n";

			foreach($package->getDependencies() as $dependency) {
				echo $dependency."\n";
			}

		}

		throw new VoidAction();

	});
?>
