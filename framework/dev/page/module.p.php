<?php
(new Page())
	/**
	 * Build a DIA environment in the specified package
	 *
	 */
	->cli('index', function($data) {

		$flags = strtoupper(GET('flags'));
		$selectedModule = GET('module');
		$selectedPackage = GET('package');


		if($flags === '') {
			throw new LineAction("Error: Argument flags=? is missing (type ".LIME_REQUEST_PATH.":help)");
		}

		if(strpos($flags, 'B') or strpos($flags, 'T') or strpos($flags, 'D')) {

			if(
				$selectedPackage === '' or
				(ctype_alnum($selectedPackage) === FALSE and $selectedPackage !== '*')
			) {
				throw new LineAction("Error: Argument package=? is mandatory with flags B, T, D (type ".LIME_REQUEST_PATH.":help)");
			}

			if(
				$selectedModule === '' or
				(ctype_alnum($selectedModule) === FALSE and $selectedModule !== '*')
			) {
				throw new LineAction("Error: Argument module=? is mandatory with flags B, T, D (type ".LIME_REQUEST_PATH.":help)");
			}

		} else if($flags === 'E') {

			if($selectedModule === '') {
				$selectedModule = '*';
			}

			if($selectedPackage === '') {
				$selectedPackage = '*';
			}

		}

		$libModule = new dev\ModuleLib();
		$libModule->load();

		$classes = $libModule->getClasses();

		if($classes === []) {
			throw new LineAction("Error: No valid class selected");
		}

		$found = 0;

		if(strpos($flags, 'E') !== FALSE) {

			foreach($classes as $class) {

				list($package, $module) = explode('\\', $class);

				if($flags === 'E') {

					if($selectedPackage !== '*' and $selectedPackage !== $package) {
						continue;
					}

					if($selectedModule !== '*' and $selectedModule !== $module) {
						continue;
					}

				}

				echo $class.": ";

				try {
					$libModule->buildModule($class);
					echo 'OK';
					$found++;
				}
				catch(Exception $e) {
					dev\ErrorPhpLib::handle($e);
				}

				echo "\n";

			}

		}

		$command = '';

		foreach(getConstants() as $name => $value) {
			$command .= " -c ".$name."=".$value."";
		}

		$actions = [
			'D' => 'finalize',
			'T' => 'init',
			'B' => 'rebuild'
		];

		foreach($actions as $flag => $action) {

			if(strpos($flags, $flag) !== FALSE) {

				foreach($classes as $class) {

					list($package, $module) = explode('\\', $class);

					if($selectedPackage !== '*' and $selectedPackage !== $package) {
						continue;
					}

					if($selectedModule !== '*' and $selectedModule !== $module) {
						continue;
					}

					$output = dev\SystemLib::command(LIME_APP, '-e '.LIME_ENV.' dev/module:'.$action.' package='.$package.' module='.$module);

					echo implode("\n", $output)."\n";
					$found++;

				}

			}
		}

		if($found === 0) {
			throw new LineAction("Error: No class found");
		}

	})
	/**
	 * Display help
	 */
	->cli('help', function($data) {

		echo "Usage: dev/module package=[package name] module=[module name] ...\n";
		echo "\n";
		echo "DESCRIPTION\n";
		echo "	Build modules from a Yaml file.\n";
		echo "\n";
		echo "OPTIONS\n";
		echo "	package=[package name]\n";
		echo "		The name of the package (use * for all packages).\n";
		echo "		This option is MANDATORY for DTB flags.\n";
		echo "	module=[module name]\n";
		echo "		Limit the build to the specified module (use * for all modules).\n";
		echo "		This option is MANDATORY for DTB flags.\n";
		echo "	flags=MDTB\n";
		echo "		E : build element / query files (automatically build files for all packages and all modules).\n";
		echo "		D : drop module table.\n";
		echo "		T : create module table.\n";
		echo "		B : rebuild module table.\n";

	})
	->cli('init', function($data) {

		$package = GET('package');
		$module = GET('module');

		try {

			(new ModuleAdministration($package.'\\'.$module))->init();

		} catch(Exception $e) {
			throw new LineAction("Error: Processing error for '".$module."' (".str_replace("\n", " ", $e->getMessage()).")");
		}

		throw new LineAction('Init '.$module.': OK');

	})
	->cli('finalize', function($data) {

		$package = GET('package');
		$module = GET('module');

		try {

			(new ModuleAdministration($package.'\\'.$module))->finalize();

		} catch(Exception $e) {
			throw new LineAction("Error: Processing error for '".$module."' (".str_replace("\n", " ", $e->getMessage()).")");
		}

		throw new LineAction('Finalize '.$module.': OK');

	})
	->cli('rebuild', function($data) {

		$package = GET('package');
		$module = GET('module');
		$default = GET('default', 'array');

		try {

			(new ModuleAdministration($package.'\\'.$module))->rebuild($default);

		} catch(Exception $e) {
			throw new LineAction("Error: Processing error for '".$module."' (".str_replace("\n", " ", $e->getMessage()).")");
		}

		throw new LineAction('Rebuild '.$module.': OK');

	});
?>