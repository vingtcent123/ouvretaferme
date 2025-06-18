<?php
namespace company;

class InviteLib extends InviteCrud {

	public static function getPropertiesCreate(): array {
		return ['email'];
	}

	public static function getByKey(string $key): Invite {

		return Invite::model()
			->select(Invite::getSelection())
			->whereKey($key)
			->get();

	}

	public static function getByCompany(Company $eCompany): \Collection {

		return Invite::model()
			->select(Invite::getSelection())
			->whereCompany($eCompany)
			->getCollection();

	}

	public static function deleteByEmail(string $email): void {

		Invite::model()
			->whereEmail($email)
			->delete();
	}
	public static function extends(Invite $e): void {

		$e->offsetUnset('id');
		$e->offsetUnset('key');
		$e->offsetUnset('expiresAt');

		self::create($e);

	}

	public static function create(Invite $e): void {

		$e->expects([
			'company' => ['name']
		]);

		Invite::model()->beginTransaction();

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
			'status',
			'company' => ['name'],
			'employee' => ['user'],
		]);

		if($e['status'] === Invite::ACCEPTED) {
			return TRUE;
		}

		if($eUser->empty()) {
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

			self::acceptEmployee($e, $eUser);

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

		self::accept($e, $e['employee']['user'], bypassExistingUser: TRUE);

		\user\User::model()->commit();

		return TRUE;

	}

	public static function deleteFromEmployee(Employee $e): void {

		$e->expects(['id', 'status', 'user']);

		if($e['status'] !== Employee::INVITED) {
			return;
		}

		EmployeeLib::delete($e);

	}

	public static function acceptByUser(\user\User $eUser): void {

		Invite::model()
			->select(Invite::getSelection() + [
				'company' => ['name']
			])
			->whereEmail($eUser['email'])
			->whereStatus(Invite::PENDING)
			->whereExpiresAt('>=', new \Sql('CURDATE()'))
			->getCollection()
			->map(fn(Invite $e) => self::accept($e, $eUser));

	}

	protected static function acceptEmployee(Invite $e, \user\User $eUser): void {

		EmployeeLib::associateUser($e['employee'], $eUser);

	}

}
?>
