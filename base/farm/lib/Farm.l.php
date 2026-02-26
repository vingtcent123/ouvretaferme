<?php
namespace farm;

class FarmLib extends FarmCrud {

	private static ?\Collection $cFarmOnline = NULL;

	public static function getPropertiesCreate(): array {
		return ['name', 'legalEmail', 'legalCountry', 'quality'];
	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Farm $e) {

			$properties = ['name', 'legalEmail', 'description', 'startedAt', 'cultivationPlace', 'cultivationLngLat', 'url', 'quality'];

			if($e->isVerified()) {

				if($e->isLegal()) {

					$properties = array_merge($properties, ['legalName', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity']);

					if($e->isFR()) {
						$properties[] = 'siret';

						if(\pdp\PdpLib::isActive($e)) {
							$properties[] = 'electronicScheme';
							$properties[] = 'electronicAddress';
						}
					}

				}

			} else {
				$properties[] = 'legalCountry';
			}

			return $properties;

		};

	}

	public static function getPropertiesCountry(Farm $e): array {

		return $e->isVerified() === FALSE ? ['legalCountry', 'verified'] : [];

	}

	public static function getPropertiesLegal(Farm $e): array {

		$properties = ['legalName', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity'];

		if($e->isVerified() === FALSE) {
			$properties[] = 'legalCountry';
			$properties[] = 'verified';
		}

		if($e->isFR()) {
			$properties[] = 'siret';
		}

		return $properties;

	}

	public static function getOnline(): \Collection {

		if(self::$cFarmOnline === NULL) {
			$eUser = \user\ConnectionLib::getOnline();
			self::$cFarmOnline = self::getByUser($eUser);
		}

		return self::$cFarmOnline;

	}

	public static function getList(?array $properties = NULL): \Collection {

		return Farm::model()
			->select($properties ?? Farm::getSelection())
			->whereStatus(Farm::ACTIVE)
			->sort('name')
			->getCollection();

	}

	public static function getByUser(\user\User $eUser): \Collection {

		return Farm::model()
			->join(Farmer::model(), 'm1.id = m2.farm')
			->select(Farm::getSelection())
			->where('m2.user', $eUser)
			->where('m1.status', Farm::ACTIVE)
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByUsers(\Collection $cUser, ?string $role = NULL): \Collection {

		return Farm::model()
			->select(Farm::getSelection())
			->join(Farmer::model(), 'm1.id = m2.farm')
			->where('m2.user', 'IN', $cUser)
			->where('m2.role', $role, if: ($role !== NULL))
			->where('m1.status', Farm::ACTIVE)
			->getCollection();

	}

	public static function getFromQuery(string $query, ?array $properties = []): \Collection {

		if(strpos($query, '#') === 0 and ctype_digit(substr($query, 1))) {

			\farm\Farm::model()->whereId(substr($query, 1));

		} else {

			\farm\Farm::model()->where('
				place LIKE '.\farm\Farm::model()->format('%'.$query.'%').' OR
				name LIKE '.\farm\Farm::model()->format('%'.$query.'%').'
			');

		}

		return \farm\Farm::model()
			->select($properties ?: Farm::getSelection())
			->whereStatus(\farm\Farm::ACTIVE)
			->sort([
				new \Sql('IF(place LIKE '.\farm\Farm::model()->format('%'.$query.'%').', 1, 0) + IF(name LIKE '.\farm\Farm::model()->format('%'.$query.'%').', 2, 0) DESC'),
				'name' => SORT_DESC
			])
			->getCollection(0, 20);

	}

	public static function connectDatabase(\farm\Farm $eFarm): void {

		$base = FarmSetting::getDatabaseName($eFarm);

		foreach(FarmSetting::getPackages() as $package) {
			\Database::setPackage($package, $base);
		}

		\Database::addBase($base, 'ouvretaferme');

		Farm::setConnected($eFarm);

		\ModuleModel::resetDatabases();

	}

	public static function create(Farm $e): void {

		Farm::model()->beginTransaction();

		$e['seasonFirst'] = date('Y');
		$e['seasonLast'] = date('n') >= (FarmSetting::NEW_SEASON - 1) ? nextYear() : date('Y');

		Farm::model()->insert($e);

		if(isset($e['owner'])) {

			$eFarmer = new Farmer([
				'user' => $e['owner'],
				'farm' => $e,
				'status' => Farmer::IN,
				'role' => Farmer::OWNER
			]);

			Farmer::model()->insert($eFarmer);

			$ePresence = new \hr\Presence([
				'farm' => $e,
				'user' => $eFarmer['user'],
				'from' => (date('Y') - 1).'-01-01'
			]);

			\hr\Presence::model()->insert($ePresence);

		}

		ConfigurationLib::createForFarm($e);

		\farm\ActionLib::duplicateForFarm($e);
		\plant\PlantLib::duplicateForFarm($e);
		\selling\UnitLib::duplicateForFarm($e);
		\payment\MethodLib::duplicateForFarm($e);

		Farm::model()->commit();

		// Create database
		if(OTF_DEMO === FALSE) {
			self::createDatabase($e);
		}

	}

	public static function createDatabase(Farm $e): void {

		Farm::model()->pdo()->exec('CREATE DATABASE IF NOT EXISTS '.FarmSetting::getDatabaseName($e));

		self::connectDatabase($e);

		new \ModuleAdministration('securing\Signature')->init();

		foreach(\company\CompanyLib::PDP_MODULES as $class) {
			new \ModuleAdministration($class)->init();
		}

	}

	public static function update(Farm $e, array $properties): void {

		Farm::model()->beginTransaction();

		// Les notes de stocks laissées vides restent à '' pour éviter de les désactiver
		if(in_array('stockNotes', $properties)) {

			if($e['stockNotes'] === NULL) {
				$e['stockNotes'] = '';
			}

			$e['stockNotesUpdatedAt'] = new \Sql('NOW()');
			$e['stockNotesUpdatedBy'] = \user\ConnectionLib::getOnline();

			$properties[] = 'stockNotesUpdatedAt';
			$properties[] = 'stockNotesUpdatedBy';

		}

		parent::update($e, $properties);

		// On fait suivre le pays tant qu'il n'a pas été vérifié par l'utilisateur
		if(
			in_array('verified', $properties) and
			$e['verified']
		) {

			Configuration::model()
				->whereFarm($e)
				->update([
					'defaultVat' => \selling\SellingSetting::getStartVat($e['legalCountry'])
				]);

		}

		if(in_array('status', $properties)) {

			Farmer::model()
				->whereFarm($e)
				->update([
					'farmStatus' => $e['status']
				]);

		}

		Farm::model()->commit();

	}

	public static function updateSeasonFirst(Farm $e, int $increment): void {

		if($increment !== -1 and $increment !== 1) {
			Farm::fail('seasonFirst.check');
			return;
		}

		Farm::model()
			->where('seasonFirst + '.$increment.' <= seasonLast')
			->update($e, [
				'seasonFirst' => new \Sql('seasonFirst + '.$increment)
			]);

	}

	public static function updateSeasonLast(Farm $e, int $increment): void {

		if($increment !== -1 and $increment !== 1) {
			Farm::fail('seasonLast.check');
			return;
		}

		Farm::model()
			->where('seasonLast + '.$increment.' BETWEEN seasonFirst AND '.((int)date('Y') + 10).'')
			->update($e, [
				'seasonLast' => new \Sql('seasonLast + '.$increment)
			]);

	}

	public static function updateStockNotesStatus(Farm $e, bool $enable): void {

		Farm::model()
			->update($e, [
				'stockNotes' => $enable ? '' : NULL,
				'stockNotesUpdatedAt' => NULL,
				'stockNotesUpdatedBy' => new \user\User()
			]);

	}

	public static function delete(Farm $e): void {

		throw new \UnsupportedException();

	}

	public static function createNextSeason(): void {

		$newSeason = nextYear();

		Farm::model()
			->where('seasonLast < '.$newSeason)
			->update([
				'seasonLast' => $newSeason
			]);

	}

	public static function getSiretApi(string $query): ?array {

		if(Farm::checkSiret($query) === FALSE) {
			return NULL;
		}

		$params = [
			'q' => $query,
			'page' => 1,
			'per_page' => 1
		];

		$curl = new \util\CurlLib();

		try {
			$values = $curl->exec('https://recherche-entreprises.api.gouv.fr/search', $params);
		} catch(\Exception) {
		}

		if($curl->getLastInfos()['httpCode'] !== 200) {
			return NULL;
		}

		$data = json_decode($values, TRUE)['results'];

		if($data === []) {
			return NULL;
		}

		$company = $data[0];

		return [
			'siren' => $company['siren'],
			'legalName' => $company['nom_raison_sociale'] ? mb_ucwords($company['nom_raison_sociale']) : ($company['nom_complet'] ? mb_ucwords($company['nom_complet']) : NULL),
			'legalCity' => mb_ucwords($company['siege']['libelle_commune']),
			'legalPostcode' => $company['siege']['code_postal'],
			'legalStreet1' => mb_ucwords(($company['siege']['numero_voie'] ? $company['siege']['numero_voie'].' ' : '').($company['siege']['type_voie'] ? $company['siege']['type_voie'].' ' : '').$company['siege']['libelle_voie']),
			'legalStreet2' => $company['siege']['complement_adresse'] ? mb_ucwords($company['siege']['complement_adresse']) : NULL,
			'isOrganic' => $company['complements']['est_bio']
		];

	}

}
