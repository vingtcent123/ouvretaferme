<?php
namespace company;

class CompanyLib {

	public static function load(\stdClass $data): void {

		$data->eFarm = \farm\FarmLib::getById(REQUEST('farm'));

		if($data->__page['type'] === 'remote') {
			$data->eFarm->validate();
		} else {
			$data->eFarm->validate('canAccounting');
		}

		if(
			str_starts_with(LIME_REQUEST, '/comptabilite/decouvrir') or
			str_starts_with(LIME_REQUEST, '/company/public:doInitialize')
		) {
			return;
		}

		if($data->eFarm->hasAccounting()) {
			\farm\FarmLib::connectDatabase($data->eFarm);
		} else {
			throw new \RedirectAction('/comptabilite/decouvrir?farm='.$data->eFarm['id']);
		}

		if($data->eFarm->usesAccounting()) {

			$cFinancialYear = \account\FinancialYearLib::getAll();

			if(get_exists('financialYear')) {

				$eFinancialYear = $cFinancialYear[GET('financialYear', 'int')] ?? $cFinancialYear->first();

				\farm\FarmerLib::setView('viewAccountingYear', $data->eFarm, $eFinancialYear);

			} else if($data->eFarm->getView('viewAccountingYear')->notEmpty()) {

				$eFinancialYear = $cFinancialYear[$data->eFarm->getView('viewAccountingYear')['id']];

			} else if($cFinancialYear->count() > 0) {

				$eFinancialYear = $cFinancialYear->first();

			} else {

				$cFinancialYear = new \Collection();
				$eFinancialYear = new \account\FinancialYear();

			}

		} else {
			$cFinancialYear = new \Collection();
			$eFinancialYear = new \account\FinancialYear();
		}

		$data->eFarm['cFinancialYear'] = $cFinancialYear;
		$data->eFarm['eFinancialYear'] = $eFinancialYear;

	}

	public static function enableAccounting(\farm\Farm $eFarm): void {

		if($eFarm->hasAccounting() === TRUE) {
			return;
		}

		if(OTF_DEMO === FALSE) {
			self::createDatabase($eFarm);
		}

		\farm\Farm::model()->update($eFarm, [
			'hasAccounting' => TRUE
		]);

	}

	public static function initializeAccounting(\farm\Farm $eFarm, array $input): void {

		$fw = new \FailWatch();

		$eFinancialYear = new \account\FinancialYear();

		$input['eFarm'] = $eFarm;
		$eFinancialYear->build(['accountingType', 'startDate', 'endDate', 'hasVat', 'vatFrequency', 'taxSystem', 'legalCategory', 'associates'], $input);

		$fw->validate();

		\account\FinancialYear::model()->beginTransaction();

		\account\FinancialYear::model()->insert($eFinancialYear);

		\farm\Farm::model()->update($eFarm, ['hasFinancialYears' => TRUE]);

		\account\FinancialYear::model()->commit();

	}

	public static function createDatabase(\farm\Farm $eFarm): void {

		// Connect database
		\farm\FarmLib::connectDatabase($eFarm);

		// Create packages tables
		$libModule = new \dev\ModuleLib();
		$libModule->load();

		$classes = $libModule->getClasses();

		foreach($classes as $class) {

			list($package) = explode('\\', $class);
			if(in_array($package, \farm\FarmSetting::getAccountingPackages())) {
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
		$database = new \account\AccountModel()->getDatabase();
		$pdo->exec('ALTER TABLE '.$pdo->api->field($database).'.'.$pdo->api->field('account').' AUTO_INCREMENT = '.\account\AccountSetting::FIRST_CUSTOM_ID);

		// Copy journalCodes
		$cJournalCode = JournalCode::model()
			->select(\journal\JournalCode::getSelection())
			->getCollection();
		foreach($cJournalCode as $eJournalCode) {
			\journal\JournalCode::model()->insert($eJournalCode);
		}

	}

	public static function rebuildTables(\farm\Farm $eFarm): void {

		d($eFarm['id']);

		\farm\FarmLib::connectDatabase($eFarm);

		// Recrée les modules puis crée ou recrée toutes les tables
		$libModule = new \dev\ModuleLib();
		$libModule->load();

		$classes = $libModule->getClasses();
		foreach($classes as $class) {
			$libModule->buildModule($class);
			list($package, $module) = explode('\\', $class);
			if($module !== GET('module')) {
				continue;
			}
			if(in_array($package, \farm\FarmSetting::getPackages()) === FALSE) {
				continue;
			}
			echo $class."\n";
			try {
				new \ModuleAdministration($class)->rebuild([]);
			} catch (\Exception $e) {
				try {
					new \ModuleAdministration($class)->init();
				} catch (\Exception $e) {
					if(LIME_ENV === 'dev') {
						return;
					}
				}
			}

		}

		// Cas particulier de la table Account : s'il n'y a pas encore eu de compte custom, il faut replacer le next auto increment
		if(GET('module') === 'Account') {

			$maxId = \account\Account::model()
				->select(['id' => new \Sql('MAX(id)', 'int')])
				->getValue('id');

			if($maxId < \account\AccountSetting::FIRST_CUSTOM_ID) {

				$db = new \Database(new \account\AccountModel()->getPackage());
				$database = new \account\AccountModel()->getDatabase();
				$db->exec('ALTER TABLE '.\account\Account::model()->field($database).'.`account` AUTO_INCREMENT = '.\account\AccountSetting::FIRST_CUSTOM_ID);

			}
		}

	}

}
