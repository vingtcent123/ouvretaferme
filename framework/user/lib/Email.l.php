<?php

namespace user;

/**
 * Handle features relative to user and mail sending
 */
class EmailLib {

	/**
	 * Return true if the user has verified his last specified mail address and false otherwise.
	 */
	public static function isVerified(User $eUser): bool {

		$eUser->expects(['email', 'verified']);

		return (
			$eUser['email'] !== NULL and
			$eUser['verified'] === TRUE
		);

	}

	/**
	 * Sends the account closing confirmation mail
	 */
	public static function sendClose(User $eUser) {

		$eUser->expects(['id', 'email']);

		if($eUser['email'] === NULL) {
			return FALSE;
		}

		$content = UserUi::getCloseMail();

		new \mail\SendLib()
			->setTo($eUser['email'])
			->setContent(...$content)
			->send();
	}

	/**
	 * Send the hash to the user's by email.
	 */
	public static function sendSignUp(User $eUser) {

		$eUser->expects(['id', 'email']);

		if($eUser['email'] === NULL) {
			return FALSE;
		}

		$content = UserUi::getSignUpMail($eUser);

		new \mail\SendLib()
			->setTo($eUser['email'])
			->setContent(...$content)
			->send();

	}

	/**
	 * Send the hash to the user's by email to verify the address.
	 */
	public static function sendVerify(
		User $eUser,
		bool $change
	) {

		$eUser->expects(['id', 'email']);

		$hash = self::computeHash($eUser);

		$content = UserUi::getVerifyMail($eUser, $hash, $change);

		new \mail\SendLib()
			->setTo($eUser['email'])
			->setContent(...$content)
			->send();

	}

	/**
	 * Verify that the $actualHash match this specific $eUser.
	 */
	public static function validate(string $actualHash) {

		$value = \main\CryptLib::decrypt($actualHash, 'user');

		if($value === NULL) {
			return User::fail('invalidHash');
		}

		[$userId, $userEmail] = explode('/', $value);

		$eUser = UserLib::getById($userId, ['id', 'email']);

		if(
			$eUser->empty() or
			$eUser['email'] !== $userEmail
		) {
			return User::fail('invalidHash');
		}

		if(ConnectionLib::isLogged()) {

			$eUserLogged = ConnectionLib::getOnline();

			if($eUser['id'] !== $eUserLogged['id']) {
				return User::fail('hashConnectedWrongAccount');
			}

		}


		// Swith the user as verified
		$eUser['verified'] = TRUE;

		\user\User::model()
			->select('verified')
			->update($eUser);

	}

	/**
	 * Create a hash based on a user id and an email.
	 */
	public static function computeHash(User $eUser) {

		$eUser->expects(['id', 'email']); // Mail can be null

		return \main\CryptLib::encrypt($eUser['id'].'/'.$eUser['email'], 'user');

	}

}
?>
