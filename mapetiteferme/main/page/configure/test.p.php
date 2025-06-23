<?php
new Page()
->cli('index', function($data) {

	/**
	 * Init pour tester sur l'entreprise #1
	 */
	$eFarm = \farm\FarmLib::getById(7);

	\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

	$databaseName = \company\CompanyLib::getDatabaseNameFromCompany($eFarm);
	\Database::addBase($databaseName, 'otf-default');

	$packagesToAdd = [];
	foreach(\company\CompanyLib::$specificPackages as $package) {
		$packagesToAdd[$package] = $databaseName;
	}
	$packages = \Database::getPackages();
	\Database::setPackages(array_merge($packages, $packagesToAdd));

	/**
	 * Là tu peux faire ce que tu veux
	 */

	$eFinancialYear = \account\FinancialYearLib::getById(1);
	\journal\VatLib::balance($eFinancialYear);

});
?>
