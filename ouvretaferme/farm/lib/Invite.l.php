<?php
namespace farm;

class InviteLib extends InviteCrud {

	public static function getPropertiesCreate(): array {
		return ['email'];
	}

	public static function getByCustomer(\selling\Customer $eCustomer): Invite {

		$eCustomer->expects(['farm']);

		return Invite::model()
			->select(Invite::getSelection())
			->whereFarm($eCustomer['farm'])
			->whereCustomer($eCustomer)
			->get();

	}

	public static function getByKey(string $key): Invite {

		return Invite::model()
			->select(Invite::getSelection())
			->whereKey($key)
			->get();

	}

	public static function extends(Invite $e): void {

		$e->offsetUnset('id');
		$e->offsetUnset('key');
		$e->offsetUnset('expiresAt');

		self::create($e);

	}

	public static function create(Invite $e): void {

		$e->expects([
			'type',
			'farm' => ['name']
		]);

		Invite::model()->beginTransaction();

		// Annulation des précédentes invitations
		switch($e['type']) {

			case Invite::CUSTOMER :

				$e->expects(['customer']);

				if(\selling\Customer::model()
					->whereUser(NULL)
					->exists($e['customer']) === FALSE) {
					return;
				}

				Invite::model()
					->whereFarm($e['farm'])
					->whereType($e['type'])
					->whereCustomer($e['customer'])
					->delete();

				break;

			case Invite::FARMER :

				$e->expects(['farmer']);

				if(Farmer::model()
					->or(
						// On continue si...
						fn() => $this->whereUser(NULL), // Soit pas d'utilisateur encore affecté à ce farmer
						fn() => $this->whereFarmGhost(TRUE) // Soit c'est un fantôme
					)
					->exists($e['farmer']) === FALSE) {
					return;
				}

				Invite::model()
					->whereFarm($e['farm'])
					->whereType($e['type'])
					->whereFarmer($e['farmer'])
					->delete();

				break;

		}

		$e['email'] = mb_strtolower($e['email']);

		Invite::model()->insert($e);

		Invite::model()->commit();

		$content = InviteUi::getInviteMail($e);

		(new \mail\MailLib())
			->addTo($e['email'])
			->setContent(...$content)
			->send('user');



	}

	public static function accept(Invite $e, \user\User $eUser, bool $bypassExistingUser = FALSE): bool {

		$e->expects([
			'type', 'status',
			'farm' => ['name']
		]);

		if($e['type'] === Invite::FARMER) {
			$e->expects([
				'farmer' => ['user'],
			]);
		}

		if($e['status'] === Invite::ACCEPTED) {
			return TRUE;
		}

		if($eUser->empty()) {
			return FALSE;
		}

		// Le cas où le Farmer est associé à un utilisateur existant est géré à part
		if(
			$e['type'] === Invite::FARMER and
			$bypassExistingUser === FALSE and
			$e['farmer']['user']->notEmpty()
		) {
			return FALSE;
		}

		Invite::model()->beginTransaction();

		$affected = Invite::model()
			->whereId($e['id'])
			->whereStatus(Invite::PENDING)
			->whereEmail($eUser['email'])
			->whereExpiresAt('>=', new \Sql('CURDATE()'))
			->update([
				'status' => Invite::ACCEPTED,
				'key' => NULL
			]);

		if($affected) {

			match($e['type']) {
				Invite::CUSTOMER => self::acceptCustomer($e, $eUser),
				Invite::FARMER => self::acceptFarmer($e, $eUser)
			};

			$content = InviteUi::getAcceptMail($e);

			(new \mail\MailLib())
				->addTo($e['email'])
				->setContent(...$content)
				->send('user');

		}

		Invite::model()->commit();

		return ($affected > 0);

	}

	public static function acceptUser(Invite $e, \user\User $eUser): bool {

		\user\User::model()->beginTransaction();

		\user\SignUpLib::updateEmail($eUser, TRUE);
		\user\SignUpLib::createPassword($eUser);

		$eUser['visibility'] = \user\User::PUBLIC;
		\user\UserLib::update($eUser, ['visibility']);

		self::accept($e, $e['farmer']['user'], bypassExistingUser: TRUE);

		\user\User::model()->commit();

		return TRUE;

	}

	public static function deleteFromFarmer(Farmer $e): void {

		$e->expects(['id', 'status', 'farmGhost', 'user']);

		if($e['status'] !== Farmer::INVITED) {
			return;
		}

		// Précédemment un fantôme, retourne comme tel
		if($e['farmGhost']) {

			Invite::model()->beginTransaction();

			Farmer::model()->update($e, [
				'status' => Farmer::IN
			]);

			Invite::model()
				->whereFarmer($e)
				->delete();

			Invite::model()->commit();

		}
		// Sinon tout dégage
		else {
			FarmerLib::delete($e);
		}

	}

	public static function deleteFromCustomer(\selling\Customer $e): void {

		Invite::model()
			->whereCustomer($e)
			->delete();

	}

	public static function acceptByUser(\user\User $eUser): void {

		Invite::model()
			->select(Invite::getSelection() + [
				'farm' => ['name']
			])
			->whereEmail($eUser['email'])
			->whereStatus(Invite::PENDING)
			->whereExpiresAt('>=', new \Sql('CURDATE()'))
			->getCollection()
			->map(fn(Invite $e) => self::accept($e, $eUser));

	}

	protected static function acceptCustomer(Invite $e, \user\User $eUser): void {

		\selling\CustomerLib::associateUser($e['customer'], $eUser);

	}

	protected static function acceptFarmer(Invite $e, \user\User $eUser): void {

		FarmerLib::associateUser($e['farmer'], $eUser);

	}

}
?>
