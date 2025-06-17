<?php
namespace user;

/**
 * Drop a user
 */
class DropLib {

	use \Notifiable;


	/**
	 * Checks if user can close its account in the current session
	 *
	 * @return bool
	 */
	public static function canClose(): bool {

		$loggedSince = \util\DateLib::interval(
			time(),
			\session\SessionLib::get('userLoggedAt')
		);

		return (
			$loggedSince < \Setting::get('closeTimeLimit') * 60
		);

	}

	/**
	 * Close user account
	 *
	 */
	public static function changeClose(User $eUser): bool {

		$eUser->expects(['id', 'deletedAt']);

		if($eUser['deletedAt']) {
			$eUser['deletedAt'] = NULL;
		} else {
			$eUser['deletedAt'] = User::model()->now('datetime', \Setting::get('closeTimeout').' DAY');
		}

		$affected = User::model()
			->select('deletedAt')
			->update($eUser);

		if($affected) {
			\session\SessionLib::set('userDeletedAt', $eUser['deletedAt']);
		}

		if($eUser['deletedAt'] and $eUser['email'] !== NULL) {
			self::notify('close', $eUser);
		}

		return TRUE;

	}

	/**
	 * Close accounts
	 */
	public static function closeExpired() {

		$cUser = User::model()
			->select(['id', 'visibility'])
			->where('deletedAt IS NOT NULL AND deletedAt < NOW()')
			->whereStatus('!=', User::CLOSED)
			->recordset()
			->getCollection();

		foreach($cUser as $eUser) {

			self::closeNow($eUser);

		}

	}

	/**
	 * Close now a user
	 * For admin purpose only
	 *
	 */
	public static function closeNow(User $eUser) {

		$eUser->expects(['id', 'visibility']);

		self::notify('dropClose', $eUser);

		User::model()->beginTransaction();

		User::model()
			->update($eUser, [
				'email' => NULL,
				'deletedAt' => new \Sql('NOW()'),
				'status' => User::CLOSED
			]);

		if($eUser['visibility'] === User::PUBLIC) {

			UserAuth::model()
				->whereUser($eUser)
				->update([
					'user' => NULL,
					'userArchive' => $eUser,
					'loginArchive' => new \Sql('login'),
					'login' => NULL,
				]);

			\session\SessionLib::killByUser($eUser);

		}

		User::model()->commit();

	}

	/**
	 * Delete a user from the database
	 *
	 */
	public static function delete(User $eUser) {

		self::notify('dropDelete', $eUser);

		UserAuth::model()
			->whereUser($eUser)
			->delete();

		User::model()->delete($eUser);

	}

}
?>
