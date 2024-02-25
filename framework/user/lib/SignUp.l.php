<?php
namespace user;

/**
 * Handle user signup and profile edition
 */
class SignUpLib {

	use \Notifiable;

	/**
	 * Check if terms of service have been accepted
	 */
	public static function checkTos(array $input) {

		$tos = (bool)($input['tos'] ?? FALSE);

		if($tos === FALSE) {
			User::fail('tos.accepted');
		}

	}

	/**
	 * Check if a user can be created with basic authentication using the given input
	 * - email: user[email] *
	 */
	public static function match(string $auth, User $eUser, array $input): bool {

		$eUser->add([
			'visibility' => User::PUBLIC,
			'auth' => new UserAuth()
		]);

		$fw = new \FailWatch;

		$eUser->build(['role', 'email', 'birthdate', 'firstName', 'lastName'], $input);

		if($eUser->offsetExists('id') === FALSE) {

			self::notify('signUpCheck', $auth, $eUser, $input);

		}

		if($fw->ok()) {

			$eUser['auth']['type'] = $auth;
			$eUser['auth']['login'] = $eUser['email'];

			return TRUE;

		} else {
			return FALSE;
		}

	}

	/**
	 * Check if a password is valid using the given input
	 * - password/passwordBis : userAuth[password] *
	 */
	public static function matchBasicPassword(string $action, User $eUser, array $input) {

		if($action === 'update') {

			$eUser['auth'] = UserAuth::model()
				->select('id', 'password')
				->whereType(\user\UserAuth::BASIC)
				->whereUser($eUser)
				->get();

			// The user might come from social connection
			if($eUser['auth']->empty()) {

				self::match('update', UserAuth::BASIC, $eUser, $input);

				if(UserAuth::model()->whereLogin($eUser['email'])->whereType(UserAuth::BASIC)->exists()) {
					User::fail('email.duplicate');
					return;
				}

				$eUser['auth'] = new UserAuth([
					'user' => $eUser,
					'type' => UserAuth::BASIC,
					'login' => $eUser['email']
				]);

			} else {

				if(password_verify($input['passwordOld'] ?? NULL, $eUser['auth']['password'])) {
					UserAuth::fail('passwordOld.invalid');
				}

			}

		}

		$eUser['auth']->build(['password'], $input, [

			'password.check' => function($password) {
				return (
					$password !== NULL and
					strlen($password) >= \Setting::get('passwordSizeMin')
				);
			},

			'password.hash' => function(&$password) {

				// Valid password, we hash it
				$password = password_hash($password, PASSWORD_DEFAULT);

				return TRUE;

			},

			'password.match' => function($password) use ($input) {

				if(array_key_exists('passwordBis', $input)) {
					return password_verify($input['passwordBis'], $password);
				} else {
					// Sometimes we don't ask for a bis password
					return TRUE;
				}

			}

		]);

	}

	/**
	 * Create a new user in the database.
	 */
	public static function create(\Element $eUser, bool $verifiedByDefault = TRUE) {

		$eUser->expects(['auth', 'visibility']);

		$eUser['country'] = UserLib::getCountry();

		// Required by doLogIn() for log in just after create
		$eUser['status'] = User::ACTIVE;
		$eUser['deletedAt'] = NULL;
		$eUser['seniority'] = 1;
		$eUser['verified'] = $verifiedByDefault;
		$eUser['onlineToday'] = FALSE;

		// We check if this IP is banned before creating the account
		if(\Feature::get('user\ban')) {

			$eBan = BanLib::getByIp(getIp());

			if($eBan->empty() === FALSE) {

				// In case of specific registration methods we store data to generate the good error message after redirection.
				if($eUser['auth']['type'] !== UserAuth::BASIC) {
					\session\SessionLib::set('activeBanForUser', $eBan);
				}

				return User::fail('signUpBanned', ['eBan' => $eBan]);
			}

		}

		try {

			User::model()->beginTransaction();

			// Add user
			User::model()->insert($eUser);

			try {

				// Add user authentication
				$eUser['auth']['user'] = $eUser;
				UserAuth::model()->insert($eUser['auth']);

				$fw = new \FailWatch;

				self::notify('signUpCreate', $eUser);

				if($fw->ko()) {

					User::model()->rollBack();

					self::notify('dropDelete', $eUser);

					return FALSE;

				} else {
					User::model()->commit();
				}

			}
			catch(\DuplicateException $e) { // UserAuth.login

				User::model()->rollBack();

				return User::fail('email.duplicate');

			}

		}
		catch(\DuplicateException $e) { // User.email

			User::model()->rollBack();

			$eUserCheck = User::model()
				->select('id', 'email')
				->whereEmail($eUser['email'])
				->get();

			if($eUserCheck->empty() === FALSE) {

				$cUserAuth = UserAuth::model()
					->select('user', 'type')
					->whereUser($eUserCheck)
					->getCollection(NULL, NULL, 'type');

				if($cUserAuth->offsetExists(UserAuth::BASIC)) {
					User::fail('email.signUpDuplicate');
					return FALSE;
				}

			}

			User::fail('email.duplicate');
			return FALSE;

		}

		return TRUE;

	}

	/**
	 * Checks if a user can update its email/password
	 */
	public static function canUpdate(User $eUser): array {

		if($eUser->empty()) {

			return [
				'email' => FALSE,
				'drop' => FALSE,
				'hasPassword' => FALSE,
				'cUserAuth' => new \Collection()
			];

		}

		$cUserAuth = UserAuth::model()
			->select('type')
			->whereUser($eUser)
			->getCollection(NULL, NULL, 'type');

		return [
			'drop' => ($cUserAuth->offsetExists(UserAuth::IMAP) === FALSE or $cUserAuth->count() > 1),
			'email' => ($cUserAuth->offsetExists(UserAuth::IMAP) === FALSE or $cUserAuth->count() > 1),
			'hasPassword' => $cUserAuth->offsetExists(UserAuth::BASIC),
			'cUserAuth' => $cUserAuth
		];

	}

	/**
	 * Update email of an existing user.
	 */
	public static function updateEmail(User $eUser, bool $verified = FALSE): bool {

		$eUser->expects(['id', 'email']);

		try {

			User::model()->beginTransaction();

			// Update email
			if(User::model()
				->select('email')
				->update($eUser) > 0) {

				// In case email has change we re init the verified flag
				$eUser['verified'] = $verified;
				$eUser['bounce'] = FALSE;

				User::model()
					->select('verified', 'bounce')
					->update($eUser);

				// Update basic auth
				UserAuth::model()
					->whereUser($eUser)
					->whereType(UserAuth::BASIC)
					->update(['login' => $eUser['email']]);

				$eUserOnline = ConnectionLib::getOnline();

				if(
					$eUserOnline->notEmpty() and
					$eUserOnline['id'] === $eUser['id']
				) {

					\session\SessionLib::set('user', new User([
						'id'=> $eUser['id']
					]));

				}

				if($eUser['verified'] === FALSE) {
					$change = TRUE;
					self::notify('sendVerifyEmail', $eUser, $change);
				}

				$properties = ['email'];
				self::notify('update', $eUser, $properties);

			}

			User::model()->commit();

		} catch(\DuplicateException $e) { // User.email

			User::model()->rollBack();

			return User::fail('email.duplicate');

		}

		return TRUE;

	}

	/**
	 * Create a new user in the database
	 */
	public static function updatePassword(User $eUser): bool {

		$eUser->expects(['auth' => ['password']]);

		if(UserAuth::model()
				->whereUser($eUser)
				->whereType(UserAuth::BASIC)
				->exists() === FALSE) {

			UserAuth::model()->insert($eUser['auth']);
			self::updateEmail($eUser);

		} else {

			UserAuth::model()
				->select('password')
				->whereType(UserAuth::BASIC)
				->update($eUser['auth']);

		}

		UserAuto::model()
			->whereUser($eUser)
			->delete();

		return TRUE;

	}

	/**
	 * Create a password for a user
	 */
	public static function createPassword(User $eUser): bool {

		$eUser->expects(['auth' => ['password']]);

		$eUserAuth = $eUser['auth'];
		$eUserAuth['user'] = $eUser;
		$eUserAuth['type'] = UserAuth::BASIC;

		try {
			UserAuth::model()->insert($eUserAuth);
		} catch(\DuplicateException) {
			self::updatePassword($eUser);
		}

		return TRUE;

	}

}
?>
