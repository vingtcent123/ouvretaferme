<?php
new Page()
->cli('index', function($data) {

	$cCompany = \company\CompanyLib::getList();

	foreach($cCompany as $eCompany) {
		d($eCompany['id']);

		\company\CompanyLib::connectSpecificDatabaseAndServer($eCompany);

		$databaseName = \company\CompanyLib::getDatabaseNameFromCompany($eCompany);
		\Database::addBase($databaseName, 'mapetiteferme-default');

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
				(new \ModuleAdministration($class))->init();
			} catch (\Exception $e) {
				(new \ModuleAdministration($class))->rebuild([]);
			}
		}

	}

});
?>
