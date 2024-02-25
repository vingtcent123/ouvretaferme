<?php
namespace user;

/**
 * Handle authentication
 */
class ConnectionLib {

	use \Notifiable;

	private static $eUserOnline = NULL;

	/**
	 * Checks if a user is currently connected
	 *
	 * @throws Action A redirect action to /user/log:form if user is not connected
	 */
	public static function isLogged(): bool {

		return (self::getOnline()->empty() === FALSE);

	}

	/**
	 * Checks if a user is currently connected
	 *
	 * @throws Action A redirect action to /user/log:form if user is not connected
	 */
	public static function checkLogged(string $redirect = LIME_URL): void {

		if(self::isLogged() === FALSE) {
			throw new \RedirectAction(\Lime::getUrl().'/user/log:form?redirect='.urlencode($redirect));
		}

	}

	public static function loadLog(\StdClass $data): void {
		self::notify('formLog', $data);
	}

	public static function loadSignUp(\StdClass $data): void {
		self::notify('formSignUp', $data);
	}

	/**
	 * Checks if a user is currently anonymous
	 *
	 * @throws Action A redirect action to / if user is not connected
	 */
	public static function isAnonymous(): bool {

		return self::getOnline()->empty();

	}

	/**
	 * Checks if a user is currently anonymous
	 *
	 * @throws Action A redirect action to / if user is not connected
	 */
	public static function checkAnonymous(): void {

		if(self::isAnonymous() === FALSE) {

			if(get_exists('redirect')) {
				throw new \RedirectAction(GET('redirect'));
			}
			throw new \RedirectAction('/');
		}

	}

	/**
	 * Returns the user currently connected
	 *
	 * @return array
	 */
	public static function getOnline(): User {

		if(self::$eUserOnline !== NULL) {
			return self::$eUserOnline;
		}

		try {

			self::$eUserOnline = self::getFromSession();

		} catch(\Exception $e) {

			if(self::autoLogIn()) {
				self::$eUserOnline = self::getFromSession();
			} else {
				self::$eUserOnline = new User();
			}

		}

		if(self::$eUserOnline->empty() === FALSE and \session\SessionLib::isRegenerated()) {

			User::model()->update(self::$eUserOnline, [
				'ping' => new \Sql('NOW()')
			]);

		}

		\Setting::set('main\onlineUser', self::$eUserOnline);

		return self::$eUserOnline;

	}

	public static function isOnline(User $eUser): bool {

		$eUser->expects(['id']);

		$eUserOnline = self::getOnline();

		return (
			$eUserOnline->notEmpty() and
			$eUserOnline['id'] === $eUser['id']
		);

	}

	protected static function getFromSession(): User {

		$eUser = \session\SessionLib::get('user');

		User::model()
			->select(User::getSelection() + [
				'role' => ['can', 'fqn']
			])
			->get($eUser);

		return $eUser;

	}

	/**
	 * Autologs the user if he asked before and the cookie is valid
	 *
	 */
	public static function autoLogIn(): bool {

		$cookie = COOKIE('autologin');

		if(strpos($cookie, '.') === FALSE) {
			return FALSE;
		}

		list($key, $id) = explode('.', $cookie);

		$eUserAuto = UserAuto::model()
			->select([
				'user' => self::selectLogIn(),
				'id'
			])
			->whereKey($key)
			->whereId($id)
			->whereStatus(UserAuto::ACTIVE)
			->get();

		if($eUserAuto->empty() === FALSE) {

			// Deletes the old cookie from the DB if there is an old one
			self::deleteAutoLoginCookie($eUserAuto['user'], $eUserAuto['id']);

			// Check if the user or his IP is currently banned
			if(
				\Feature::get('user\ban') and
				BanLib::isBanned($eUserAuto['user'], getIp())
			) {

				return FALSE;

			} else {

				// Log user in
				if(self::doLogIn($eUserAuto['user'], Log::LOGIN_AUTO)) {

					UserAuth::model()
						->whereUser($eUserAuto['user'])
						->update([
							'loggedAt' => new \Sql('NOW()'),
						]);

					self::setAutoLoginCookie($eUserAuto['user'], $eUserAuto['id']);

					return TRUE;

				}

			}

		}

		return FALSE;


	}

	/**
	 * Sets the autologin cookie according to the user
	 * Generates a new key
	 *
	 */
	protected static function setAutoLoginCookie(User $eUser) {

		$eUser->expects(['id']);

		// Generates a new key
		$eUserAuto = new UserAuto([
			'user' => $eUser,
			'key' => hash('sha256', random_bytes(1024)),
			'expiresAt' => new \Sql('NOW() + INTERVAL 30 DAY')
		]);

		$lifetime = 30;

		UserAuto::model()->insert($eUserAuto);

		// Add this new key to the user's autologin cookie
		$key = $eUserAuto['key'].'.'.$eUserAuto['id'];
		setCookie('autologin', $key, time() + 3600 * 24 * $lifetime, '/', '.'.\Lime::getDomain(), FALSE, TRUE);

	}

	/**
	 * Deletes the autologin cookie for the user who asked for disconnection
	 *
	 *
	 */
	protected static function deleteAutoLoginCookie(User $eUser, int $idUserAuto) {

		$eUserAuto = new UserAuto([
			'id' => $idUserAuto,
			'user' => $eUser,
			'status' => UserAuto::DELETED
		]);

		UserAuto::model()
			->select('status')
			->whereId($eUserAuto['id'])
			->whereUser($eUserAuto['user'])
			->update($eUserAuto);

		// Unsets the login auto if the user had one
		setcookie('autologin', FALSE, time() - 3600 * 24, '/', '.'.\Lime::getDomain(), FALSE, TRUE);
		unset($_COOKIE['autologin']);

	}

	/**
	 * Get redirect after login
	 */
	public static function getRedirectLogin(): ?string {

		$eUser = ConnectionLib::getOnline();

		if(
			User::model()
				->select(['email', 'verified', 'deletedAt', 'bounce'])
				->get($eUser) and
			$eUser['bounce'] === TRUE
		) {
			return \Lime::getUrl().'/user/update:email';
		}

		foreach(self::notify('logInRedirect') as $result) {

			if($result) {
				return $result;
			}

		}

		$defaultRedirect = self::getDefaultRedirect();

		if($defaultRedirect !== NULL) {

			$redirect = $defaultRedirect.'?';
			$redirect .= self::getCustomRedirect($redirect);

		} else {

			$redirect = NULL;

		}

		return $redirect;

	}

	public static function getCustomRedirect(string $redirect): string {

		$redirect = self::notify('loginCustom', $redirect);

		if(empty($redirect)) {
			return '';
		}

		return first($redirect);

	}

	/**
	 * Get default redirection
	 */
	private static function getDefaultRedirect(): ?string {

		if(request_exists('redirect')) {

			$request = REQUEST('redirect');
			$request = \util\HttpUi::removeArgument($request, 'success');

			if(preg_match('/^http[s]?:\/\//si', $request) > 0) {
				return $request;
			} else if(substr($request, 0, 1) === '/') {
				return \Lime::getUrl().$request;
			} else {
				return NULL;
			}

		} else {
			return '/';
		}

	}

	/**
	 * Try to log a user in
	 * This method tries available auths
	 *
	 */
	public static function logIn(string $login = NULL, string $password = NULL, array $options = []): bool {

		$fw = new \FailWatch();

		foreach(\Setting::get('auth') as $auth => $params) {

			if(is_string($params)) {
				$auth = $params;
				$params = [];
			}

			if($auth === UserAuth::BASIC and $login !== NULL and $password !== NULL) {
				$eUserAuth = self::logInBasic($login, $password);
			} else if($auth === UserAuth::IMAP and $login !== NULL and $password !== NULL) {
				$eUserAuth = self::logInImap($params, $login, $password);
			} else {
				$eUserAuth = new UserAuth();
			}

			if($eUserAuth->empty() === FALSE and $eUserAuth['user']->empty() === FALSE) {

				// Check user ban
				if(\Feature::get('user\ban')) {

					$eBan = BanLib::getByUser($eUserAuth['user'], getIp());

					if($eBan->empty() === FALSE) {

						// In case of specific login methods we store data to generate the good error message after redirection.
						if($auth !== UserAuth::BASIC) {
							\session\SessionLib::set('activeBanForUser', $eBan);
						}

						return User::fail('connectionBanned', ['eBan' => $eBan]);

					}

				}

				// Log user in
				if(self::doLogIn($eUserAuth['user'])) {

					// Update last log in date
					$eUserAuth['loggedAt'] = new \Sql('NOW()');

					UserAuth::model()
						->select('loggedAt')
						->update($eUserAuth);

					if(($options['remember'] ?? FALSE) === TRUE) {

						self::setAutoLoginCookie($eUserAuth['user']);

					}

					return TRUE;

				}

			}

		}

		if($fw->ok()) {
			User::fail('connectionInvalid');
		}

		return FALSE;

	}

	/**
	 * Log user in for basic authentication
	 */
	protected static function logInBasic(string $login, string $password): UserAuth {

		$eUserAuth = UserAuth::model()
			->select([
				'id',
				'password',
				'user' => self::selectLogIn()
			])
			->whereType(UserAuth::BASIC)
			->whereLogin($login)
			->get();

		if(
			$eUserAuth->empty() or
			$eUserAuth['user']->empty() or // Account deleted
			password_verify($password, $eUserAuth['password']) === FALSE // Check user password
		) {
			return new UserAuth();
		}

		return $eUserAuth;

	}

	protected static function logInImap(array $params, string $login, string $password): UserAuth {

		if(
			strpos($login, '@') === FALSE or
			strstr($login, '@') !== $params['domain']
		) {
			return new UserAuth();
		}

		$autoCreate = FALSE;

		$eUserAuth = UserAuth::model()
			->select([
				'id',
				'user' => self::selectLogIn()
			])
			->whereType(UserAuth::IMAP)
			->whereLogin($login)
			->get();

		if($eUserAuth->empty()) {

			if(empty($params['autoCreate'])) {
				return $eUserAuth;
			} else {
				$autoCreate = TRUE;
			}

		}

		$imap = self::checkImap($login, $password, $params);

		if($imap) {

			if($autoCreate) {

				$eUser = new User();

				$input = [
					'email' => $login
				];

				if(SignUpLib::match(UserAuth::IMAP, $eUser, $input)) {
					SignUpLib::create($eUser);
				} else {
					return new UserAuth();
				}

				$eUserAuth = $eUser['auth'];


			}

			return $eUserAuth;

		}

		return new UserAuth();

	}

	private static function checkImap(string $login, string $password, array $params): bool {

		if(function_exists('imap_open') === FALSE) {
			throw new \Exception('Function imap_open() does not exist', E_USER_ERROR);
		}

		\dev\ErrorPhpLib::$doNothingFromError = TRUE;

		$imap = imap_open(
				'{'.$params['host'].':'.$params['port'].'/imap/'.$params['options'].'}',
				$login,
				$password,
				OP_READONLY |  OP_HALFOPEN |  OP_SILENT,
				0
		);

		\dev\ErrorPhpLib::$doNothingFromError = FALSE;

		if($imap === FALSE) {
			imap_errors();
			imap_alerts();
			return FALSE;
		} else {
			imap_close($imap);
			return TRUE;
		}

	}

	/**
	 * Log a user in without any check
	 */
	public static function logInUser(User $eUser, bool $remember = FALSE): bool {

		$eUser->expects(['id']);

		if(

			User::model()
				->select(self::selectLogIn())
				->whereStatus(User::ACTIVE)
				->get($eUser) === FALSE or

			self::doLogIn($eUser) === FALSE

		) {
			User::fail('connectionInvalid');
			return FALSE;
		} else {

			if($remember) {
				self::setAutoLoginCookie($eUser);
			}
			return TRUE;
		}

	}

	/**
	 * Database and session processes for user login
	 *
	 */
	protected static function doLogIn(User $eUser, string $type = Log::LOGIN): bool {

		$eUser->expects(['id']);
		$eUser->expects(['email', 'status', 'deletedAt', 'onlineToday']); // These properties must be initialized in SignUpLib::create()

		$loggedAt = User::model()->now();
		$isRegularLogin = ($type === Log::LOGIN or $type === Log::LOGIN_AUTO);

		if($isRegularLogin) {

			$affected = User::model()
				->whereStatus(User::ACTIVE)
				->update($eUser, [
					'loggedAt' => $loggedAt,
					'seen' => new \Sql('seen + 1'),
					'onlineToday' => TRUE,
					'ping' => $loggedAt
				]);

			// User is not valid
			if($affected === 0) {
				return FALSE;
			}

			// Log out connected user first
			try {

				$eUserOnline = \session\SessionLib::get('user');

				self::logOut($eUserOnline);

			} catch(\Exception $e) {

			}

		}

		LogLib::add($eUser, $type);

		\session\SessionLib::set('userLoggedAt', $loggedAt);
		\session\SessionLib::set('userDeletedAt', $eUser['deletedAt']);

		\session\SessionLib::set('user', new User([
			'id'=> $eUser['id']
		]));

		if($isRegularLogin) {

			$firstTimeToday = ($eUser['onlineToday'] === FALSE);

			$firstTime = (
				$eUser['seniority'] === 1 and
				$firstTimeToday
			);

			self::notify('logIn', $eUser, $firstTime, $firstTimeToday);
		}

		self::$eUserOnline = NULL;

		return TRUE;

	}

	/**
	 * Logs $eUserAction into $eUser account
	 *
	 */
	public static function logInExternal(User $eUser, User $eUserAction): bool {

		$eUserOld = \session\SessionLib::get('user');

		if(\session\SessionLib::exists('userOld')) {
			return FALSE;
		}

		// First : logOut $eUserAction
		self::logOut($eUserAction);

		// Second : logIn $eUserAction as if it was $eUser
		self::doLogIn($eUser, Log::LOGIN_EXTERNAL);

		\session\SessionLib::set('userOld', $eUserOld);

		return TRUE;

	}

	/**
	 * Logs out from the user's account and relogs in the good account
	 *
	 * @param bool
	 */
	public static function logOutExternal(): bool {

		try {
			$eUserOld = \session\SessionLib::get('userOld');
		} catch(\Exception $e) {
			return FALSE;
		}

		$eUser = self::getOnline();

		// logs out from the previous account
		self::logOut($eUser);

		if(User::model()
			->select(self::selectLogIn())
			->get($eUserOld)) {

			// Second : logIn as the good user
			self::doLogIn($eUserOld, Log::LOGIN);

		}

		return TRUE;

	}

	/**
	 * Log a user out
	 *
	 */
	public static function logOut(User $eUser): bool {

		$eUser->expects(['id']);

		LogLib::add($eUser, Log::LOGOUT);

		\session\SessionLib::clean();

		$cookie = COOKIE('autologin');

		if(strpos($cookie, '.') !== FALSE) {

			list($key, $idUserAuto) = explode('.', $cookie);
			self::deleteAutoLoginCookie($eUser, $idUserAuto);
		}

		self::notify('logOut', $eUser);

		return TRUE;

	}

	/**
	 * Required properties to log a user in
	 */
	public static function selectLogIn(): array {

		return [
			'id', 'email', 'status', 'deletedAt', 'onlineToday', 'seniority', 'bounce', 'role'
		];

	}

	/**
	 * Checks if the user is connected onto another account
	 */
	public static function checkLoginExternal(): ?array {

		if(\session\SessionLib::exists('userOld') === FALSE) {
			return NULL;
		}

		$eUserAction = \session\SessionLib::get('userOld');
		$eUser = self::getOnline();

		return [$eUser, $eUserAction];

	}

}
?>
