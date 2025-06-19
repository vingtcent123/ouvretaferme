<?php
namespace company;

class CompanyLib extends CompanyCrud {

	private static ?\Collection $cCompanyOnline = NULL;
	public static array $specificPackages = ['accounting', 'asset', 'bank', 'journal', 'pdf'];

	public static function getPropertiesCreate(): array {
		return ['name', 'nafCode', 'siret', 'addressLine1', 'addressLine2', 'postalCode', 'city'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'nafCode', 'addressLine1', 'addressLine2', 'postalCode', 'city', 'accountingType', 'isBio'];
	}

	public static function getOnline(): \Collection {

		if(self::$cCompanyOnline === NULL) {
			$eUser = \user\ConnectionLib::getOnline();
			self::$cCompanyOnline = self::getByUser($eUser);
		}

		return self::$cCompanyOnline;

	}

	public static function getCurrent(): Company {

		$eCompany = \company\CompanyLib::getById(REQUEST('company'));
		if($eCompany->empty()) {
			return $eCompany;
		}

		if(self::getOnline()->find(fn($e) => $e['id'] === $eCompany['id'])) {
			return $eCompany;
		}

		return new Company();

	}

	public static function getList(?array $properties = NULL): \Collection {

		return Company::model()
			->select($properties ?? Company::getSelection())
			->whereStatus(Company::ACTIVE)
			->sort('name')
			->getCollection();

	}

	public static function getBySiret(string $siret): Company {

		$eCompany = new Company();

		Company::model()
      ->select(Company::getSelection())
			->whereSiret($siret)
			->whereStatus(Company::ACTIVE)
      ->get($eCompany);

		return $eCompany;

	}

	public static function getByUser(\user\User $eUser): \Collection {

		return Company::model()
			->join(Employee::model(), 'm1.id = m2.company')
			->select(Company::getSelection())
			->where('m2.user', $eUser)
			->where('m1.status', Company::ACTIVE)
			->getCollection(NULL, NULL, 'id');

	}

	public static function getById(mixed $id, array $properties = []): Company {

		$eCompany = new Company();

		Company::model()
      ->select(
				Company::getSelection() + [
						'cSubscription' => Subscription::model()
              ->select(Subscription::getSelection())
							->sort(['endsAt' => SORT_DESC])
              ->delegateCollection('company')
					],
      )
			->whereId($id)
      ->get($eCompany);

		return $eCompany;

	}

	public static function getByUsers(\Collection $cUser, ?string $role = NULL): \Collection {

		return Company::model()
			->select(Company::getSelection())
			->join(Employee::model(), 'm1.id = m2.company')
			->where('m2.user', 'IN', $cUser)
			->where('m2.role', $role, if: ($role !== NULL))
			->where('m1.status', Company::ACTIVE)
			->getCollection();

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
			->whereStatus(\company\Company::ACTIVE)
			->sort([
				'name' => SORT_DESC
			])
			->getCollection(0, 20);

	}

	public static function create(Company $e): void {

    Company::model()->beginTransaction();

    Company::model()->insert($e);

		if(isset($e['owner'])) {

			$eEmployee = new Employee([
				'user' => $e['owner'],
				'company' => $e,
				'status' => Employee::IN,
				'role' => Employee::OWNER,
			]);

			Employee::model()->insert($eEmployee);

		}

		self::createSpecificDatabaseAndTables($e);

    Company::model()->commit();

	}

	public static function connectSpecificDatabaseAndServer(Company $eCompany): void {

		$base = self::getDatabaseName($eCompany);

		foreach(self::$specificPackages as $package) {
			\Database::addPackages([$package => $base]);
		}

		\Database::addBase($base, 'mapetiteferme-default');

	}

	public static function getDatabaseName(Company $eCompany): string {

		if(LIME_ENV === 'prod') {
			return'mapetiteferme_'.$eCompany['id'];
		}

		return 'dev_mapetiteferme_'.$eCompany['id'];
	}

	public static function createSpecificDatabaseAndTables(Company $eCompany): void {

		// Create database
		new \ModuleAdministration('company\Company')->createDatabase(CompanyLib::getDatabaseNameFromCompany($eCompany));

		// Connect database
		self::connectSpecificDatabaseAndServer($eCompany);

		// Create packages tables
		$libModule = new \dev\ModuleLib();
		$libModule->load();

		$classes = $libModule->getClasses();

		foreach($classes as $class) {

			list($package) = explode('\\', $class);
			if(in_array($package, self::$specificPackages)) {
				(new \ModuleAdministration($class))->init();
			}

		}

		// Copy Account content from package main to package accounting
		$cAccount = \main\GenericAccount::model()
			->select(\main\GenericAccount::getSelection())
			->getCollection();
		foreach($cAccount as $eAccount) {
			\accounting\Account::model()->insert($eAccount);
		}
	}

  public static function getDatabaseNameFromCompany(Company $e): string {

    return \Database::getPackages()[Company::model()->getPackage()].'_'.$e['id'];

  }

	public static function update(Company $e, array $properties): void {

    Company::model()->beginTransaction();

		parent::update($e, $properties);

		if(in_array('status', $properties)) {

			Employee::model()
				->whereCompany($e)
				->update([
					'companyStatus' => $e['status']
				]);

		}

		if(in_array('isBio', $properties) and $e['isBio']) {

			\company\SubscriptionLib::subscribe($e, CompanyElement::PRODUCTION, isBio: $e['isBio']);
			\company\SubscriptionLib::subscribe($e, CompanyElement::SALES, isBio: $e['isBio']);

		}

    Company::model()->commit();

	}

	public static function delete(Company $e): void {

    (new \ModuleAdministration('company\Company'))->dropDatabase(CompanyLib::getDatabaseNameFromCompany($e));

	}

}
