<?php
namespace farm;

class FarmLib extends FarmCrud {

	private static ?\Collection $cFarmOnline = NULL;

	public static function getPropertiesCreate(): array {
		return ['name', 'legalEmail', 'legalCountry', 'quality'];
	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Farm $e) {

			$properties = ['name', 'legalName', 'legalEmail', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity', 'legalCountry', 'description', 'startedAt', 'cultivationPlace', 'cultivationLngLat', 'url', 'quality'];

			if($e->isFR()) {
				$properties[] = 'siret';
			}

			return $properties;

		};

	}

	public static function getPropertiesLegal(Farm $e): array {

		$properties = ['legalName', 'legalCountry', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity'];

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

		\selling\ConfigurationLib::createForFarm($e);

		\farm\ActionLib::duplicateForFarm($e);
		\plant\PlantLib::duplicateForFarm($e);

		Farm::model()->commit();

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

		throw new \Exception('Not implemented');

	}

	public static function createNextSeason(): void {

		$newSeason = nextYear();

		Farm::model()
			->where('seasonLast < '.$newSeason)
			->update([
				'seasonLast' => $newSeason
			]);

	}

}
