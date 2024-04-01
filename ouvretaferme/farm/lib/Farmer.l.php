<?php
namespace farm;

class FarmerLib extends FarmerCrud {

	private static ?\Collection $cFarmerOnline = NULL;

	public static function getPropertiesCreate(): array {
		return ['id', 'role', 'email'];
	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Farmer $e) {

			if($e->canUpdateRole() === FALSE) {
				return [];
			} else {
				return ['role'];
			}

		};

	}

	public static function getOnline(): \Collection {

		if(self::$cFarmerOnline === NULL) {
			$eUser = \user\ConnectionLib::getOnline();
			self::$cFarmerOnline = self::getByUser($eUser);
		}

		return self::$cFarmerOnline;

	}

	public static function getOnlineByFarm(Farm $eFarm): Farmer {
		return self::getOnline()[$eFarm['id']] ?? new Farmer();
	}

	public static function create(Farmer $e): void {

		$e->expects(['id', 'role']);

		$isGhost = $e['farmGhost'] ?? FALSE;

		// On lance une invitation si ce n'est pas un utilisateur fantôme ou si c'est une tentative de transformer un fantôme qui pré-existe
		if(
			$isGhost === FALSE or
			$e['id'] !== NULL
		) {

			$eInvite = new Invite([
				'farm' => $e['farm'],
				'type' => Invite::FARMER,
				'farmer' => $e
			]);

			$fw = new \FailWatch();

			$eInvite->buildProperty('email', $e['email']);

			if($fw->ko()) {
				return;
			}

		} else {
			$eInvite = new Invite();
		}

		try {

			Farmer::model()->beginTransaction();

			if($e['id'] === NULL) {

				Farmer::model()->insert($e);

			} else {

				Farmer::model()->update($e, [
					'role' => $e['role'],
					'status' => Farmer::INVITED
				]);

			}

			if($eInvite->notEmpty()) {
				InviteLib::create($eInvite);
			}

			Farmer::model()->commit();


		} catch(\DuplicateException $e) {

			Farmer::model()->rollBack();
			Farmer::fail('duplicate');

		}

	}

	public static function isFarmer(\user\User $eUser, Farm $eFarm, ?string $status = Farmer::IN): bool {

		if($status !== NULL) {
			Farmer::model()->whereStatus($status);
		}

		return Farmer::model()
			->whereUser($eUser)
			->whereFarm($eFarm)
			->whereFarmStatus(Farmer::ACTIVE)
			->exists();

	}

	public static function getByUser(\user\User $eUser): \Collection {

		return Farmer::model()
			->select(Farmer::getSelection())
			->whereUser($eUser)
			->whereFarmStatus(Farmer::ACTIVE)
			->whereStatus(Farmer::IN)
			->getCollection(NULL, NULL, 'farm');

	}

	public static function getByFarm(Farm $eFarm, bool $onlyInvite = FALSE, $onlyGhost = FALSE): \Collection {

		$sort = [];

		$eUserOnline = \user\ConnectionLib::getOnline();

		if($eUserOnline->notEmpty()) {
			$sort[] = new \Sql('user = '.$eUserOnline['id'].' DESC');
		}

		$sort['createdAt'] = SORT_ASC;

		Farmer::model()->sort($sort);

		if($onlyGhost) {
			Farmer::model()->whereFarmGhost(TRUE);
		} else if($onlyInvite) {
			Farmer::model()
				->select([
					'invite' => Invite::model()
						->select(Invite::getSelection())
						->whereStatus(Invite::PENDING)
						->delegateElement('farmer')
				])
				->whereStatus(Farmer::INVITED);
		} else {
			Farmer::model()->whereStatus(Farmer::IN);
		}

		return Farmer::model()
			->select(Farmer::getSelection())
			->whereFarm($eFarm)
			->getCollection();

	}

	public static function getUsersByFarm(Farm $eFarm, bool $selectInvite = FALSE, bool $onlyGhost = FALSE, bool $withPresenceAbsence = FALSE): \Collection {

		$cUser = self::getByFarm($eFarm, $selectInvite, $onlyGhost)
			->getColumnCollection('user', 'user')
			->sort([
				'firstName' => SORT_ASC,
				'lastName' => SORT_ASC
			]);

		if($withPresenceAbsence) {
			\hr\PresenceLib::fillUsers($eFarm, $cUser);
			\hr\AbsenceLib::fillUsers($eFarm, $cUser);
		}

		return $cUser;

	}

	public static function getUsersByFarmForTasks(Farm $eFarm, \Collection $cTask, bool $withPresenceAbsence = FALSE): \Collection {

		$cUser = self::getUsersByFarmForPeriod(
			$eFarm,
			week_date_starts(currentWeek()),
			week_date_ends(currentWeek())
		);

		$weeks = array_merge($cTask->getColumn('plannedWeek'), $cTask->getColumn('doneWeek'));

		if($weeks) {

			$cUser->appendCollection(

				self::getUsersByFarmForPeriod(
					$eFarm,
					week_date_starts(min($weeks)),
					week_date_ends(max($weeks))
				)

			);

		}

		$cUser->appendCollection(

			\series\Timesheet::model()
				->select([
					'user' => \user\User::getSelection()
				])
				->whereFarm($eFarm)
				->whereTask('IN', $cTask)
				->getCollection()
				->getColumnCollection('user', 'user')
		);

		$cUser->sort([
			'firstName' => SORT_ASC,
			'lastName' => SORT_ASC
		]);

		if($withPresenceAbsence) {
			\hr\PresenceLib::fillUsers($eFarm, $cUser);
			\hr\AbsenceLib::fillUsers($eFarm, $cUser);
		}

		return $cUser;

	}

	public static function getUsersByFarmForPeriod(Farm $eFarm, string $start, string $stop, bool $withPresenceAbsence = FALSE): \Collection {

		$cUser =\hr\PresenceLib::getBetween($eFarm, $start, $stop)
			->getColumnCollection('user', 'user')
			->sort([
				'firstName' => SORT_ASC,
				'lastName' => SORT_ASC
			]);

		if($withPresenceAbsence) {
			\hr\PresenceLib::fillUsers($eFarm, $cUser);
			\hr\AbsenceLib::fillUsers($eFarm, $cUser);
		}

		return $cUser;

	}

	public static function createGhostUser(Farm $eFarm, \user\User $eUser) {

		Farmer::model()->beginTransaction();

		$fw = new \FailWatch();

		\user\UserLib::create($eUser);

		if($fw->ok()) {

			$eFarmer = new Farmer([
				'id' =>  NULL,
				'farm' => $eFarm,
				'farmGhost' => TRUE,
				'role' => NULL,
				'user' => $eUser,
				'status' => Farmer::OUT
			]);

			self::create($eFarmer);

		}

		Farmer::model()->commit();

	}

	public static function deleteGhostUser(Farm $eFarm, \user\User $eUser) {

		Farmer::model()->beginTransaction();

		$affected = Farmer::model()
			->whereFarmGhost(TRUE)
			->whereStatus(Farmer::OUT)
			->whereFarm($eFarm)
			->whereUser($eUser)
			->delete();

		if($affected > 0) {
			\user\DropLib::closeNow($eUser);
		}

		Farmer::model()->commit();

	}

	public static function associateUser(Farmer $e, \user\User $eUser): void {

		$affected = Farmer::model()
			->whereStatus(Farmer::INVITED)
			->update($e, [
				'user' => $eUser,
				'farmGhost' => FALSE,
				'status' => Farmer::IN
			]);

		if($affected) {

			// L'utilisateur passe forcément Farmer

			$eRoleNew = \user\RoleLib::getByFqn('farmer');

			\user\User::model()->update($eUser, [
				'role' => $eRoleNew
			]);

		}

	}

	public static function delete(Farmer $e): void {

		$e->expects(['farmGhost']);

		if($e['farmGhost']) {
			Farmer::fail('deleteGhost');
			return;
		}

		if(
			$e['user']->notEmpty() and
			$e['user']['id'] === \user\ConnectionLib::getOnline()['id']
		) {
			Farmer::fail('deleteItself');
			return;
		}

		Farmer::model()->beginTransaction();

		parent::delete($e);

		Invite::model()
			->whereFarmer($e)
			->delete();

		Farmer::model()->commit();

	}

	public static function register(Farm $eFarm): void {

		$eFarmer = self::getOnlineByFarm($eFarm);

		$properties = array_filter(Farmer::model()->getProperties(), fn($property) => str_starts_with($property, 'view'));

		foreach($properties as $property) {
			\Setting::set('main\\'.$property, $eFarmer->notEmpty() ? $eFarmer[$property] : Farmer::model()->getDefaultValue($property));
		}

	}

	/**
	 * Get season from $season or from Farmer::$viewSeason
	 */
	public static function getDynamicSeason(Farm $eFarm, int $season): int {

		$eFarm->expects(['id', 'seasonLast']);

		if($season) {

			if($eFarm->checkSeason($season) === FALSE) {
				$season = $eFarm['seasonLast'];
			}

			\farm\FarmerLib::setView('viewSeason', $eFarm, $season);

			return $season;

		} else {
			return \Setting::get('main\viewSeason') ?? $eFarm['seasonLast'];
		}

	}

	public static function setView(string $field, Farm $eFarm, mixed $newView): mixed {

		$eFarmer = self::getOnlineByFarm($eFarm);

		if($eFarmer->empty()) {
			return $newView;
		}

		if($newView === $eFarmer[$field]) {
			return $eFarmer[$field];
		}

		if(Farmer::model()->check($field, $newView)) {

			$eFarmer[$field] = $newView;

			Farmer::model()
				->select($field)
				->update($eFarmer);

			\Setting::set('main\\'.$field, $eFarmer[$field]);


		}

		return $eFarmer[$field];


	}

}
