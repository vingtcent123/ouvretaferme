<?php
namespace user;

/**
 * User basic functions
 */
class UserLib extends UserCrud {

	use \Notifiable;

	/**
	 * Cache for country list
	 *
	 * @var \Collection
	 */
	private static ?\Collection $cCountryList = NULL;

	public static function getPropertiesUpdate(): array {
		return ['firstName', 'lastName', 'phone', 'street1', 'street2', 'postcode', 'city'];
	}

	public static function count(): int {

		return \Cache::redis()->query('user-count', function() {

			return User::model()
				->whereStatus(User::ACTIVE)
				->count();

		}, 86400);

	}

	public static function getByEmail(string $email, array $properties = []): User {

		return User::model()
			->select($properties ?: User::getSelection())
			->whereEmail($email)
			->get();

	}

	public static function getFromQuery(string $query, ?Role $eRole = NULL, ?array $properties = []): \Collection {

		if(strpos($query, '#') === 0 and ctype_digit(substr($query, 1))) {

			\user\User::model()->whereId(substr($query, 1));

		} else {

			\user\User::model()->where('
				IF(firstName IS NULL, lastName, CONCAT(firstName, " ", lastName)) LIKE '.\user\User::model()->format('%'.$query.'%').' OR
				email = '.\user\User::model()->format($query).'
			');

		}

		if($eRole) {
			User::model()->whereRole($eRole);
		}

		return \user\User::model()
			->select($properties ?: User::getSelection())
			->whereVisibility(User::PUBLIC)
			->whereStatus(\user\User::ACTIVE)
			->getCollection(0, 20);

	}

	/**
	 * Triggers the email verification send
	 */
	public static function triggerSendVerifyEmail(User $eUser, bool $change) {
		self::notify('sendVerifyEmail', $eUser, $change);
	}

	/**
	 * Return the last known IP of a user by searching the login logs.
	 */
	public static function getLastKnownIp(User $eUser) {

		$eUser->expects(['id']);

		return Log::model()->select('ip')
			->whereUser($eUser)
			->whereAction(Log::LOGIN)
			->sort(['createdAt' => SORT_DESC])
			->getValue('ip');

	}

	/**
	 * Get all user which login actions were on the same $ip address.
	 * /!\ This method is slow and requests all the log tables /!\
	 */
	public static function getByIp(string $ip): array {

		$cLog = Log::model()
			->select(['uniqUser' => new \Sql('DISTINCT(user)')])
			->whereAction(Log::LOGIN)
			->whereIp($ip)
			->getCollection(NULL, NULL, 'uniqUser');

		return $cLog->getColumn('uniqUser');
	}

	/**
	 * Count all user which login actions were on the same $ip address.
	 * /!\ This method is slow and requests all the log tables /!\
	 */
	public static function countByIp(string $ip): int {

		return count(self::getByIp($ip));

	}

	/**
	 * Register privileges of the given user
	 */
	public static function registerPrivileges(User $eUser) {

		if($eUser->empty()) {
			return;
		}

		$eUser->expects(['role']);

		$can = $eUser['role']['can'];

		foreach($can as $package => $privileges) {
			\Privilege::register($package, $privileges, TRUE);
		}

	}

	/**
	 * Checks that the email can be used to reset the password
	 */
	public static function checkForgottenPasswordLink(string $email, int $expires = 1): ?UserAuth {

		// 1. check the email is a basic auth
		$eUser = User::model()
			->select('id', 'email')
			->whereEmail($email)
			->get();

		if($eUser->empty()) {
			User::fail('email.check');
			return NULL;
		}

		$cUserAuth = UserAuth::model()
			->select('id', 'user', 'type')
			->whereUser($eUser)
			->getCollection(NULL, NULL, 'type');

		if($cUserAuth->count() === 0) {
			User::fail('internal');
			return NULL;
		}

		foreach([UserAuth::BASIC] as $type) {

			if(isset($cUserAuth[$type])) {

				if($type === UserAuth::BASIC) {

					// 2. generate the hash
					$eUserAuth = new UserAuth([
						'hashExpirationDate' => new \Sql('NOW() + INTERVAL '.$expires.' DAY'),
						'passwordHash' => hash('sha256', random_bytes(1024)),
						'user' => $eUser
					]);

					return $eUserAuth;

				}
			}

		}


	}

	/**
	 * Sets the hash, the expiration date of the hash and sends the email
	 */
	public static function sendForgottenPasswordLink(UserAuth $eUserAuth): bool {

		if(self::updateForgottenPasswordLink($eUserAuth)) {

			$content = UserUi::getForgottenPasswordMail(
				$eUserAuth['passwordHash'],
				$eUserAuth['user']['email']
			);

			(new \mail\MailLib())
				->addTo($eUserAuth['user']['email'])
				->setContent(...$content)
				->send('user');

			return TRUE;
		} else {
			return FALSE;
		}

	}

	/**
	 * Sets the hash, the expiration date of the hash and sends the email
	 */
	public static function updateForgottenPasswordLink(UserAuth $eUserAuth): bool {

		return UserAuth::model()
			->select('passwordHash', 'hashExpirationDate')
			->whereType(UserAuth::BASIC)
			->whereUser($eUserAuth['user'])
			->update($eUserAuth) > 0;

	}

	/**
	 * Deletes old password hashes
	 *
	 */
	public static function cleanForgottenPasswordHash(): int {

		return UserAuth::model()
			->whereType(UserAuth::BASIC)
			->where('hashExpirationDate < NOW()')
			->update('passwordHash = NULL, hashExpirationDate = NULL');

	}

	/**
	 * Deletes old password hashes
	 *
	 */
	public static function cleanForgottenPasswordHashByUser(User $eUser): int {

		return UserAuth::model()
			->whereUser($eUser)
			->where('hashExpirationDate >= NOW()')
			->whereType(UserAuth::BASIC)
			->update('passwordHash = NULL, hashExpirationDate = NULL');

	}

	/**
	 * Checks that the user has this hash
	 */
	public static function getUserByHashAndEmail(string $hash, string $email): User {

		$eUser = User::model()
			->select('id', 'email')
			->whereEmail($email)
			->get();

		if($eUser->empty()) {
			User::fail('internal');
			return new User();
		}

		$eUserAuth = UserAuth::model()
			->select('id')
			->whereUser($eUser)
			->whereType(UserAuth::BASIC)
			->wherePasswordHash($hash)
			->where('hashExpirationDate > NOW()')
			->get();

		if($eUserAuth->empty()) {
			User::fail('invalidLinkForgot');
			return new User();
		}

		$eUser['auth'] = $eUserAuth;

		return $eUser;

	}


	/**
	 * Update seniority of users
	 */
	public static function updateSeniority(): void {

		User::model()
			->whereOnlineToday(TRUE)
			->update([
				'onlineToday' => FALSE,
				'seniority' => new \Sql('seniority + 1')
			]);
	}

	public static function create(User $e, bool $verifiedByDefault = TRUE): void {

		$e->expects(['firstName', 'lastName', 'email', 'visibility']);

		$e['country'] = self::getCountry();
		$e['verified'] = $verifiedByDefault;

		try {
			User::model()->insert($e);
		}
		catch(\DuplicateException) { // User.email

			User::fail('email.duplicate');

		}

	}

	/**
	 * Gets the country for the user for the sign up
	 *
	 * @return array
	 */
	public static function getCountry(): Country {

		$eCountry = (new GeoliteLib())->getCountry();

		if($eCountry->empty()) {
			return self::getDefaultCountry();
		}

		return $eCountry;

	}

	public static function getDefaultCountry(): Country {

		return Country::model()
			->select('id')
			->whereCode('FR')
			->get();

	}

	/**
	 * Update an existing user profile
	 *
	 */
	public static function update(User $e, array $properties): void {

		$e->expects(['id']);

		// Special case for e-mail
		$email = array_search('email', $properties);

		if($email !== FALSE) {

			unset($properties[$email]);
			SignUpLib::updateEmail($e, TRUE);

		}

		array_delete($properties, 'addressMandatory');

		if($properties) {

			$affected = User::model()
				->select($properties)
				->update($e);

			if($affected > 0) {
				self::notify('update', $e, $properties);
			}

		}

	}

	public static function delete(User $e): void {
		throw new \Exception('Not implemented yet');
	}

	/**
	 * Get a list of countries
	 *
	 * @return \Collection
	 */
	public static function getCountries(): \Collection {

		if(self::$cCountryList === NULL) {

			$cCountry = Country::model()
				->select([
					'id', 'name', 'code'
				])
				->sort('name')
				->getCollection(NULL, NULL, 'id');

			self::$cCountryList = $cCountry;

		}

		return self::$cCountryList;

	}

	public function getDailyUsersStats(\Collection $cRole): \Collection {

		return User::model()
			->select([
				'role',
				'value' => new \Sql('COUNT(*)', 'int')
			])
			->whereRole('IN', $cRole)
			->wherePing('>=', new \Sql('CONCAT(CURDATE(), " 00:00:00")'))
			->whereVisibility(User::PUBLIC)
			->group('role')
			->getCollection(index: 'role');

	}

	public function getActiveUsersStats(\Collection $cRole, int $days = 30): \Collection {

		return User::model()
			->select([
				'role',
				'value' => new \Sql('COUNT(*)', 'int')
			])
			->whereRole('IN', $cRole)
			->wherePing('>=', new \Sql('NOW() - INTERVAL '.$days.' DAY'))
			->whereVisibility(User::PUBLIC)
			->group('role')
			->getCollection(index: 'role');

	}

}
?>
