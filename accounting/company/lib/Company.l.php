<?php
namespace company;

class CompanyLib {

	public static array $specificPackages = ['account', 'asset', 'bank', 'journal', 'pdf'];

	public static function connectSpecificDatabaseAndServer(\farm\Farm $eFarm): void {

		$base = self::getDatabaseName($eFarm);

		foreach(self::$specificPackages as $package) {
			\Database::setPackage($package, $base);
		}

		\Database::addBase($base, 'ouvretaferme');

	}

	public static function getDatabaseName(\farm\Farm $eFarm): string {

		if(OTF_DEMO) {
			return 'ouvretaferme';
		}

		if(LIME_ENV === 'prod') {
			return 'farm_'.$eFarm['id'];
		}

		return 'dev_farm_'.$eFarm['id'];
	}

	public static function initializeAccounting(\farm\Farm $eFarm, array $input): void {

		\farm\Farm::model()->beginTransaction();

		$fw = new \FailWatch();

		$startDate = POST('startDate');
		if(mb_strlen($startDate) === 0 or \util\DateLib::isValid($startDate) === FALSE) {
			\Fail::log('FinancialYear::startDate.check');
		}

		$endDate = POST('endDate');
		if(mb_strlen($endDate) === 0 or \util\DateLib::isValid($endDate) === FALSE) {
			\Fail::log('FinancialYear::endDate.check');
		}

		if($startDate >= $endDate) {
			\Fail::log('FinancialYear::dates.inconsistency');
		}

		if($fw->ko()) {
			return;
		}

		self::createSpecificDatabaseAndTables($eFarm);

		$eFinancialYear = new \account\FinancialYear();
		$eFinancialYear->build(['accountingType', 'startDate', 'endDate', 'hasVat', 'vatFrequency', 'taxSystem'], $input);

		\account\FinancialYear::model()->insert($eFinancialYear);

		\farm\Farm::model()->update($eFarm, ['hasAccounting' => TRUE]);

		\farm\Farm::model()->commit();

	}

	public static function createSpecificDatabaseAndTables(\farm\Farm $eFarm): void {

		// Create database
		new \ModuleAdministration('company\GenericAccount')->createDatabase(CompanyLib::getDatabaseNameFromCompany($eFarm));

		// Connect database
		self::connectSpecificDatabaseAndServer($eFarm);

		// Create packages tables
		$libModule = new \dev\ModuleLib();
		$libModule->load();

		$classes = $libModule->getClasses();

		foreach($classes as $class) {

			list($package) = explode('\\', $class);
			if(in_array($package, self::$specificPackages)) {
				new \ModuleAdministration($class)->init();
			}

		}

		// Copy Account content from package main to package accounting
		$cAccount = \company\GenericAccount::model()
			->select(\company\GenericAccount::getSelection())
			->whereType(GenericAccount::AGRICULTURAL)
			->getCollection();
		foreach($cAccount as $eAccount) {
			\account\Account::model()->insert($eAccount);
		}
	}

  public static function getDatabaseNameFromCompany(\farm\Farm $eFarm): string {

    return (LIME_ENV === 'dev' ? 'dev_' : '').'farm_'.$eFarm['id'];

  }

	// TODO DELETE FARM
  //new \ModuleAdministration('main\GenericAccount')->dropDatabase(CompanyLib::getDatabaseNameFromCompany($e));

}
