<?php
namespace company;

class CompanyLib {

	public static array $specificPackages = ['account', 'asset', 'bank', 'journal', 'overview', 'pdf'];

	public static function connectSpecificDatabaseAndServer(\farm\Farm $eFarm): void {

		$base = self::getDatabaseName($eFarm);

		foreach(self::$specificPackages as $package) {
			\Database::setPackage($package, $base);
		}

		\Database::addBase($base, 'ouvretaferme');

	}

	public static function getDatabaseName(\farm\Farm $eFarm): string {

		if(OTF_DEMO) {
			return 'demo_ouvretaferme';
		}

		if(LIME_ENV === 'prod') {
			return 'farm_'.$eFarm['id'];
		}

		return 'dev_farm_'.$eFarm['id'];
	}

	public static function initializeAccounting(\farm\Farm $eFarm, array $input): void {

		$fw = new \FailWatch();

		$eFinancialYear = new \account\FinancialYear();

		$input['eFarm'] = $eFarm;
		$eFinancialYear->build(['accountingType', 'startDate', 'endDate', 'hasVat', 'vatFrequency', 'taxSystem', 'legalCategory', 'associates'], $input);

		$fw->validate();

		if(OTF_DEMO === FALSE) {
			self::createSpecificDatabaseAndTables($eFarm);
		}

		// Réinstanciation nécessaire car il a été précédemment instancié avec une mauvaise connexion.
		new \account\FinancialYearModel()->insert($eFinancialYear);

		\farm\Farm::model()->update($eFarm, ['hasAccounting' => TRUE]);

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
		// Set next auto-increment to 100000 (for the custom accounts)
		$pdo = new \account\AccountModel()->pdo();
		$database = new \account\AccountModel()->getDb();
		$pdo->exec('ALTER TABLE '.$pdo->api->field($database).'.'.$pdo->api->field('account').' AUTO_INCREMENT = '.\account\AccountSetting::FIRST_CUSTOM_ID);

		// Copy journalCodes
		$cJournalCode = JournalCode::model()
			->select(\journal\JournalCode::getSelection())
			->getCollection();
		foreach($cJournalCode as $eJournalCode) {
			\journal\JournalCode::model()->insert($eJournalCode);
		}
	}

  public static function getDatabaseNameFromCompany(\farm\Farm $eFarm): string {

    return (LIME_ENV === 'dev' ? 'dev_' : '').'farm_'.$eFarm['id'];

  }

	public static function rebuildTables(\farm\Farm $eFarm): void {

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
				new \ModuleAdministration($class)->rebuild([]);
			} catch (\Exception $e) {
				new \ModuleAdministration($class)->init();
			}

		}

		// Cas particulier de la table Account : s'il n'y a pas encore eu de compte custom, il faut replacer le next auto increment
		$maxId = \account\Account::model()
			->select(['id' => new \Sql('MAX(id)', 'int')])
			->getValue('id');
		if($maxId < \account\AccountSetting::FIRST_CUSTOM_ID) {
			$db = new \Database(new \account\AccountModel()->getPackage());
			$database = new \account\AccountModel()->getDb();
			$db->exec('ALTER TABLE '.\account\Account::model()->field($database).'.`account` AUTO_INCREMENT = '.\account\AccountSetting::FIRST_CUSTOM_ID);
		}

		\ModuleModel::dbClean();

	}

	// TODO DELETE FARM
  //new \ModuleAdministration('main\GenericAccount')->dropDatabase(CompanyLib::getDatabaseNameFromCompany($e));

}
