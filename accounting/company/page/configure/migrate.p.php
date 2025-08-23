<?php
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->getCollection();

		foreach($cFarm as $eFarm) {
			d($eFarm['id']);

			\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

			$databaseName = \company\CompanyLib::getDatabaseNameFromCompany($eFarm);
			\Database::addBase($databaseName, 'ouvretaferme');

			$packagesToAdd = [];
			foreach(\company\CompanyLib::$specificPackages as $package) {
				$packagesToAdd[$package] = $databaseName;
			}
			$packages = \Database::getPackages();
			\Database::setPackages(array_merge($packages, $packagesToAdd));


			// Recrée les modules puis crée ou recrée toutes les tables
			$libModule = new \dev\ModuleLib();
			$libModule->load();

			$classes = $libModule->getClasses();
			foreach($classes as $class) {
				$libModule->buildModule($class);
				list($package) = explode('\\', $class);
				if(in_array($package, \company\CompanyLib::$specificPackages) === FALSE) {
					continue;
				}
				echo $class."\n";
				try {
					new \ModuleAdministration($class)->init();
				} catch (\Exception $e) {
					new \ModuleAdministration($class)->rebuild([]);
				}
			}

		}

	});
?>
