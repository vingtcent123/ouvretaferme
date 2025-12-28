<?php
namespace farm;

class DemoLib {

	const DATABASE = 'demo_ouvretaferme';

	const USER = 1;

	const COPY_FARM = 7;

	const COPY_PACKAGE_EXCLUDE = [
		'dev', 'session', 'storage', 'mail', 'game',
		'analyze', 'shop', 'website'
	];

	const COPY_MODULE_EXCLUDE = [
		'account\Account',
		'account\FinancialYear',
		'account\Log',
		'account\Partner',
		'account\ThirdParty',
		'asset\Asset',
		'asset\Amortization',
		'association\History',
		'bank\BankAccount',
		'bank\Cashflow',
		'bank\Import',
		'journal\Lettering',
		'journal\Operation',
		'journal\OperationCashflow',
		'farm\Invite',
		'farm\Tip',
		'payment\StripeFarm',
		'pdf\Pdf',
		'pdf\Content',
		'preaccounting\Suggestion',
		'selling\Grid',
		'selling\History',
		'selling\Invoice',
		'selling\Pdf',
		'selling\PdfContent',
		'selling\Payment',
		'series\Comment',
		'user\Log',
		'user\UserAuto',
		'user\UserAuth'
	];

	const COPY_PROPERTY_EXCLUDE = [
		'user\User' => ['birthdate', 'phone', 'vignette', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceCountry'],
		'series\Repeat' => ['description'],
		'series\Series' => ['comment'],
		'selling\Sale' => ['invoice', 'deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'deliveryCountry'],
		'selling\Customer' => ['name', 'firstName', 'lastName', 'email', 'phone', 'legalName', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceCountry', 'deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'deliveryCountry']
	];

	private static array $ms = [];
	private static array $demoMs = [];

	private static ?array $names = NULL;

	public static function rebuild() {

		\company\CompanyLib::connectDatabase(new Farm(['id' => self::COPY_FARM]));
		// prod instance
		self::$ms = self::getModules();

		// demo mode
		self::activate();

		// demo instances
		self::$demoMs = self::getModules();

		// drop old demo table
		self::dropTables();

		// create new demo tables
		self::createTables();

		// copy data in demo tables
		self::copy();

		// update data
		self::update();

		// anonymize data
		self::anonymize();

	}

	public static function init(): void {

		// prod instance
		self::$ms = self::getModules();

		self::activate();

		// demo instances
		self::$demoMs = self::getModules();

	}

	public static function activate(): void {

		$newDatabase = [];

		foreach(\Database::getPackages() as $package => $database) {
			$newDatabase[$package] = self::DATABASE;
		}

		\Database::setPackages($newDatabase);

	}

	public static function dropTables(): void {

		$pdo = first(self::$demoMs)->pdo();

		$rs = $pdo->select('SHOW TABLES FROM '.$pdo->api->field(self::DATABASE));

		while($row = $rs->fetch(\PDO::FETCH_ASSOC)) {

			$table = first($row);

			$pdo->exec('DROP TABLE IF EXISTS '.$pdo->api->field(self::DATABASE).'.'.$pdo->api->field($table));

		}

	}

	public static function createTables(): void {

		foreach(self::$demoMs as $m) {

			(new \ModuleAdministration($m->getModule()))->createTable();

		}

	}

	public static function copy(): void {

		foreach(self::$ms as $module => $m) {

			if(self::canCopyModule($m) === FALSE) {
				continue;
			}

			$pdo = $m->pdo();
			$table = $m->getTable();

			$properties = $m->getProperties();
			$demoProperties = $m->getProperties();

			array_walk($demoProperties, function(&$value) use($pdo) {
				$value = $pdo->api->field($value);
			});

			array_walk($properties, function(&$value, $key) use($module, $m, $pdo) {

				if(
					($m->getModule() === 'farm\\Farm' and $value === 'id') or
					($m->getPropertyToModule('farm') === 'farm\\Farm' and $value === 'farm')
				) {
					$value = 'IF('.$pdo->api->field($value).' IS NULL, NULL, '.Farm::DEMO.') AS '.$pdo->api->field($value);
				} else if(
					($m->getModule() === 'series\\Task' and $value === 'description')
				) {
					$value = 'IF('.$pdo->api->field($value).' IS NULL, NULL, "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus.") AS '.$pdo->api->field($value);
				} else if(in_array($value, self::COPY_PROPERTY_EXCLUDE[$module] ?? [])) {
					$value = 'NULL AS '.$pdo->api->field($value);
				} else {

					$valueModule = $m->getPropertyToModule($value);

					if($valueModule) {

						// On met à NULL les propriétés des packages et modules exclus
						foreach(self::COPY_PACKAGE_EXCLUDE as $package) {
							if(str_starts_with($valueModule, $package.'\\')) {
								$value = 'NULL AS '.$pdo->api->field($value);
								return;
							}
						}

						foreach(self::COPY_MODULE_EXCLUDE as $module) {
							if($valueModule === $module) {
								$value = 'NULL AS '.$pdo->api->field($value);
								return;
							}
						}

					}

					$value = $pdo->api->field($value);

				}

			});

			$sql = 'INSERT INTO '.$pdo->api->field(self::DATABASE).'.'.$pdo->api->field($table).'('.implode(', ', $demoProperties).') SELECT '.implode(', ', $properties).' FROM '.$pdo->api->field($m->getDb()).'.'.$pdo->api->field($table);

			$sql .= ' WHERE '.self::getCopyCondition($m);

			$pdo->exec($sql);

		}

	}

	private static function getCopyCondition(\ModuleModel $m): string {

		$conditions = [];

		if($m->getModule() === 'farm\\Farm') {
			$conditions[] = 'id = '.self::COPY_FARM;
		} else if($m->hasProperty('farm')) {
			$conditions[] = '(farm = '.self::COPY_FARM.' OR farm IS NULL)';
		}

		if(
			$m->getModule() === 'selling\\Customer' or
			$m->getModule() === 'selling\\Sale' or
			$m->getModule() === 'selling\\Item'
		) {
			$conditions[] = 'type = "'.\selling\Customer::PRIVATE.'"';
		}

		if(
			$m->getModule() === 'selling\\Sale'
		) {
			$conditions[] = 'preparationStatus != "'.\selling\Sale::BASKET.'"';
		}

		if(
			$m->getModule() === 'selling\\Item'
		) {
			$conditions[] = 'status != "'.\selling\Sale::BASKET.'"';
		}

		return $conditions ? implode(' AND ', $conditions) : 1;

	}

	public static function update(): void {

		self::updateClosedSeries();


	}

	public static function updateClosedSeries(): void {

		\series\Series::model()
			->whereStatus(\series\Series::CLOSED)
			->update([
				'status' => \series\Series::OPEN
			]);


	}

	public static function anonymize(): void {

		self::anonymizeFarm();
		self::anonymizeUsers();
		self::anonymizeSales();
		self::anonymizeCustomers();


	}

	public static function anonymizeFarm(): void {

		Farmer::model()
			->whereId(Farm::DEMO)
			->update([
				'viewAccountingYear' => NULL
			]);

		Farm::model()
			->whereId(Farm::DEMO)
			->update([
				'name' => 'Ferme des Mots',
				'description' => 'Ferme de démonstration pour présenter {siteName} !',
				'logo' => NULL,
				'emailBanner' => NULL,
				'legalName' => 'GAEC de Démo',
				'legalEmail' => 'demo@ouvretaferme.org',
				'legalStreet1' => NULL,
				'legalStreet2' => NULL,
				'legalPostcode' => NULL,
				'legalCity' => NULL,
				'siret' => NULL,
				'url' => NULL,
				'hasAccounting' => FALSE,
			]);

		Configuration::model()
			->whereFarm(Farm::DEMO)
			->update([
				'hasVat' => TRUE,
				'vatNumber' => NULL,
				'invoiceHeader' => NULL,
				'invoiceFooter' => NULL
			]);

		$cGenericAccount = \company\GenericAccount::model()
			->select(\company\GenericAccount::getSelection())
			->whereType(\company\GenericAccount::AGRICULTURAL)
			->getCollection();
		foreach($cGenericAccount as $eGenericAccount) {
			$eUser = new \user\User(['id' => 1]);
			$eAccount = clone $eGenericAccount;
			$eAccount['createdBy'] = $eUser;
			\account\Account::model()->insert($eAccount);
		}

	}

	public static function anonymizeUsers(): void {

		$farmUsers = array_merge(
			new FarmerModel()->getColumn('user')->getIds(),
			new \series\TimesheetModel()
				->whereFarm(Farm::DEMO)
				->getColumn(new \Sql('DISTINCT(user)', 'int'))
		);

		$cUserCustomer = new \selling\CustomerModel()
			->whereUser('!=', NULL)
			->getColumn('user');

		new \user\UserModel()
			->whereId('NOT IN', $farmUsers)
			->whereId('NOT IN', $cUserCustomer)
			->delete();

		new \user\UserModel()
			->whereRole(new \user\RoleModel()
				->select('id')
				->whereFqn('admin')
				->get())
			->update([
				'role' => new \user\RoleModel()
					->select('id')
					->whereFqn('farmer')
					->get()
			]);

		$position = 0;

		foreach(new \user\UserModel()
        ->select('id')
        ->getCollection() as $eUser) {

			$eUser['firstName'] = self::getFirstName($eUser['id']);
			$eUser['lastName'] = self::getLastName();
			$eUser['email'] = $eUser['id'].'@'.\Lime::getDomain();

			new \user\UserModel()
				->select('firstName', 'lastName', 'email')
				->update($eUser);

			$eUserAuth = new \user\UserAuth([
				'user' => $eUser,
				'type' => \user\UserAuth::BASIC,
				'login' => $eUser['email'],
				'password' => password_hash('123456', PASSWORD_DEFAULT),
			]);

			\user\UserAuth::model()->insert($eUserAuth);

			$position++;

		}

	}

	public static function anonymizeSales(): void {

		new \selling\CustomerModel()
			->whereUser(\farm\DemoLib::USER)
			->update([
				'user' => NULL
			]);

	}

	public static function anonymizeCustomers(): void {

		foreach(new \selling\CustomerModel()
        ->select('id')
        ->getCollection() as $eCustomer) {

			$eCustomer['firstName'] = self::getFirstName($eCustomer['user']['id'] ?? $eCustomer['id']);
			$eCustomer['lastName'] = self::getLastName();
			$eCustomer['name'] = $eCustomer['firstName'].' '.$eCustomer['lastName'];

			new \selling\CustomerModel()
				->select('firstName', 'lastName', 'name')
				->update($eCustomer);

		}

	}

	public static function getModules(): array {

		$modules = [];

		foreach(\Package::getList() as $package => $app) {

			$directory = LIME_DIRECTORY.'/'.$app.'/'.$package.'/module/';

			foreach(glob($directory.'*.m.php') as $path) {

				$module = $package.'\\'.substr($path, strlen($directory), -6);

				$m = (new \ReflectionClass($module.'Model'))->newInstance();

				$modules[$module] = $m;

			}

		}

		return $modules;

	}

	public static function canCopyModule(\ModuleModel $m): bool {

		return (
			in_array($m->getPackage(), self::COPY_PACKAGE_EXCLUDE) === FALSE and
			in_array($m->getModule(), self::COPY_MODULE_EXCLUDE) === FALSE
		);

	}

	public static function getLastName(): string {
		return substr('ABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(0, 25), 1).'.';
	}

	public static function getFirstName(int $position): string {

		if(self::$names === NULL) {

			self::$names = [
				"Alma", "Louise", "Emma", "Jeanne", "Anna", "Adèle", "Rose", "Gabrielle", "Chloé", "Jade", "Élise", "Cerise", "Juliette", "Julie", "Ariane", "Sandrine", "Lola", "Léna", "Éléonore", "Karine",
				"Gabriel", "Adam", "Raphaël", "Louis", "Mohamed", "Arthur", "Isaac", "Noah", "Gaspard", "Léon", "Roméo", "Simon", "Samuel", "Vincent", "Pierre", "Franck", "Justin", "Emmanuel", "Gustave", "Thomas", "William"
			];

			shuffle(self::$names);

		}

		return self::$names[$position % count(self::$names)];

	}

}
?>
