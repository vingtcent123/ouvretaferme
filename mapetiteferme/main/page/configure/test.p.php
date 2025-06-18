<?php
new Page()
->cli('index', function($data) {

	/**
	 * Init pour tester sur l'entreprise #1
	 */
	$eCompany = \company\CompanyLib::getById(1);

	\company\CompanyLib::connectSpecificDatabaseAndServer($eCompany);

	$databaseName = \company\CompanyLib::getDatabaseNameFromCompany($eCompany);
	\Database::addBase($databaseName, 'mapetiteferme-default');

	$packagesToAdd = [];
	foreach(\company\CompanyLib::$specificPackages as $package) {
		$packagesToAdd[$package] = $databaseName;
	}
	$packages = \Database::getPackages();
	\Database::setPackages(array_merge($packages, $packagesToAdd));

	/**
	 * Là tu peux faire ce que tu veux
	 */

	$eFinancialYear = \accounting\FinancialYearLib::getById(1);
	\journal\VatLib::balance($eFinancialYear);

});
?>
