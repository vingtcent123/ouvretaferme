<?php
namespace company;

class CompanyLib extends CompanyCrud {

	private static ?Company $eCompany = NULL;

	public static array $specificPackages = ['account', 'asset', 'bank', 'journal', 'pdf'];

	public static function getPropertiesCreate(): array {
		return ['accountingType'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function getList(?array $properties = NULL): \Collection {

		return Company::model()
			->select($properties ?? Company::getSelection())
			->getCollection();

	}

	public static function getById(mixed $id, array $properties = []): Company {

		$eCompany = new Company();

		Company::model()
      ->select(Company::getSelection())
			->whereId($id)
      ->get($eCompany);

		return $eCompany;

	}

	public static function getCurrent(): Company {

		if(self::$eCompany === NULL) {
			self::$eCompany = Company::model()
				->select(Company::getSelection())
				->whereFarm(REQUEST('farm'))
				->get();
		}

		return self::$eCompany;

	}

	public static function getByFarm(\farm\Farm $eFarm): Company {

		return Company::model()
			->select(Company::getSelection())
			->whereFarm($eFarm)
			->get();

	}

	public static function getFromQuery(string $query, ?array $properties = []): \Collection {

		if(strpos($query, '#') === 0 and ctype_digit(substr($query, 1))) {

			\company\Company::model()->whereId(substr($query, 1));

		} else {

			\company\Company::model()->where('
				name LIKE '.\company\Company::model()->format('%'.$query.'%').'
			');

		}

		return \company\Company::model()
			->select($properties ?: Company::getSelection())
			->sort([
				'name' => SORT_DESC
			])
			->getCollection(0, 20);

	}

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

	public static function createCompanyAndFinancialYear(\farm\Farm $eFarm, array $input): void {

		Company::model()->beginTransaction();

		$fw = new \FailWatch();

		$eCompany = new Company();
		$eCompany->build(['farm', 'accountingType'], $input);

		Company::model()->insert($eCompany);

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
		$eFinancialYear->build(['startDate', 'endDate', 'hasVat', 'vatFrequency', 'taxSystem'], $input);

		\account\FinancialYear::model()->insert($eFinancialYear);

		Company::model()->commit();

	}

	public static function createSpecificDatabaseAndTables(\farm\Farm $eFarm): void {

		// Create database
		new \ModuleAdministration('company\Company')->createDatabase(CompanyLib::getDatabaseNameFromCompany($eFarm));

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
			->getCollection();
		foreach($cAccount as $eAccount) {
			\account\Account::model()->insert($eAccount);
		}
	}

  public static function getDatabaseNameFromCompany(\farm\Farm $eFarm): string {

    return \Database::getPackages()[\company\GenericAccount::model()->getPackage()].'_'.$eFarm['id'];

  }

	// TODO DELETE FARM
  //new \ModuleAdministration('main\GenericAccount')->dropDatabase(CompanyLib::getDatabaseNameFromCompany($e));

}
