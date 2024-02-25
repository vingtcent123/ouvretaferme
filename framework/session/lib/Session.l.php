<?php
namespace session;

/**
 * Session package
 */
class SessionLib {

	use \Notifiable;

	/**
	 * Session content max length
	 *
	 */
	const MAX_LENGTH = 3000;

	/**
	 * Session lifetime
	 *
	 * @var int
	 */
	const LIFETIME = 10800;

	/**
	 * Timeout before session regeneration
	 *
	 * @var int
	 */
	const REGENERATION = 900;

	/**
	 *  Set the authentication cookies using the attributes 'HttpOnly' and 'Secure'.
	 *
	 * @var bool
	 */
	const SECURE_COOKIE = FALSE;

	private static string $content = ''; // Session content
	private static bool $regenerate = FALSE; // Must we regenerate session ?

	protected static ?\Cache $cache = NULL;
	protected static string $cachePrefix = 'session-';

	/**
	 * Has session been regenerated ?
	 *
	 * @return bool
	 */
	public static function isRegenerated(): bool {
		return self::$regenerate;
	}

	/**
	 * Init session
	 *
	 */
	public static function init(string $domain = NULL, string $path = NULL): void {

		if(isset($_SESSION) === FALSE) {

			session_set_save_handler(
				function(string $path, string $name): bool {
					return TRUE;
				},
				function(): bool {
					return TRUE;
				},
				function(string $sid) {
					return self::read($sid);
				},
				function(string $sid, string $content) {
					return self::write($sid, $content);
				},
				function(string $sid): bool {
					return TRUE;
				},
				function(int $maxLifeTime): bool {
					return TRUE;
				},
				function(): string {
					return self::createSid();
				}
			);

			register_shutdown_function('session_write_close');

			if($domain === NULL or $path === NULL) {
				$domain = '.'.\Lime::getDomain();
				$path = '/';

			}

			if(
				static::SECURE_COOKIE and
				(SERVER('HTTPS') === 'on')
			) {

				session_set_cookie_params(0, $path, $domain, static::SECURE_COOKIE, static::SECURE_COOKIE);

			} else {

				session_set_cookie_params(0, $path, $domain);

			}

			session_name('session'.LIME_ENV);

			session_start();

		}

		$time = time();

		try {
			self::$regenerate = (self::get('regenerate') <= $time);
		}
		catch(\Exception $e) {
			self::$regenerate = TRUE;
		}

		if(self::$regenerate) {
			self::set('regenerate', $time + self::REGENERATION * 2);
		}

	}

	/**
	 * Change a value in the session
	 */
	public static function set(string $name, $value): void {

		self::validate();

		$_SESSION['data'][$name] = is_object($value) ? clone $value : $value;

	}

	public static function createSid(): string {

		$strong = TRUE;
		return bin2hex(openssl_random_pseudo_bytes(11, $strong));

	}

	/**
	 * Get a value in the session
	 */
	public static function get(string $name) {

		if(self::exists($name)) {
			$value = $_SESSION['data'][$name];
			return is_object($value) ? clone $value : $value;
		} else {
			throw new \Exception('Cannot find the session key \''.$name.'\'');
		}

	}

	/**
	 * Check if a value exists in the session
	 */
	public static function exists(string $name): bool {

		self::validate();

		if(isset($_SESSION['data'])) {
			return array_key_exists($name, $_SESSION['data']);
		} else {
			return FALSE;
		}

	}

	/**
	 * Unset a value in the session
	 */
	public static function delete(string $name): void {

		self::validate();

		unset($_SESSION['data'][$name]);

	}

	/**
	 * Delete all values in the session
	 *
	 */
	public static function clean(): void {

		self::validate();

		$_SESSION['data'] = [];

	}

	/**
	 * Checks if there is currently a valid session opened
	 */
	protected static function validate(): void {

		if(\Route::getRequestedWith() === 'cli') {
			return;
		}

		if(isset($_SESSION) === FALSE) {
			throw new \Exception("No session initialized");
		}

	}

	/**
	 * Create a new session with current content
	 */
	protected static function createSession(string $sid): bool {

		$eSession = new Session([
			'sid' => $sid,
			'content' => gzcompress(self::$content),
		]);

		$affected = Session::model()
			->option('add-ignore')
			->insert($eSession);

		if($affected > 0) {

			self::notify('create', $sid);

			self::getCache()->set(self::$cachePrefix.$sid, self::$content, self::REGENERATION * 2);
			return TRUE;

		} else {
			return FALSE;

		}

	}

	/**
	 * Delete current session but do not remove the current content
	 */
	protected static function deleteSession(string $sid): void {

		$affected = Session::model()
			->whereSid($sid)
			->delete();

		self::getCache()->delete(self::$cachePrefix.$sid);

		if($affected > 0) {
			self::notify('delete', $sid);
		}

	}

	/**
	 * Read session
	 */
	public static function read(string $sid): string {

		$result = self::getCache()->get(self::$cachePrefix.$sid);

		if($result === FALSE) {

			if(Session::model()->check('sid', $sid) !== FALSE) {

				$eSession = Session::model()
					->select([
						'content',
						'lastUpdate' => new \Sql('UNIX_TIMESTAMP() - UNIX_TIMESTAMP(updatedAt)', 'int'),
					])
					->where('updatedAt >= NOW() - INTERVAL '.self::LIFETIME.' SECOND')
					->whereSid($sid)
					->get();


			} else {
				throw new \Exception('Invalid sid "'.$sid.'"');
			}

			// No existing session
			// Create a new one
			if($eSession->empty()) {

				$sid = session_id();

				self::$content = '';

				if(!self::createSession($sid)) {

					self::$content = Session::model()
						->where('updatedAt >= NOW() - INTERVAL '.self::LIFETIME.' SECOND')
						->where('sid', $sid)
						->getValue('content') ?? '';

				}
			} else {

				self::$content = gzuncompress($eSession['content']);

				if(self::$content === FALSE) {

					self::deleteSession($sid);

					self::$content = '';

				} else {

					self::getCache()->set(self::$cachePrefix.$sid, self::$content, self::REGENERATION * 2);

					if($eSession['lastUpdate'] > self::REGENERATION) {

						Session::model()
							->whereSid($sid)
							->update('updatedAt = NOW()');

					}

				}

			}

		} else {
			self::$content = $result;
		}

		return self::$content;

	}

	/**
	 * Write session
	 */
	public static function write(string $sid, string $content): bool {

		// Don't update session if regeneration date has not been reached and session has not changed
		if(self::$regenerate === FALSE and self::$content === $content) {
			return TRUE;
		}

		$properties = [
			'content' => gzcompress($content),
			'updatedAt' => new \Sql('NOW()')
		];

		$length = strlen($properties['content']);

		if($length > self::MAX_LENGTH / (LIME_ENV !== 'dev' ? 1 : 2)) {
			throw new \Exception("Session length exhausted (".$length." bytes): ".var_export(array_keys($_SESSION), TRUE));
		}

		$affected = Session::model()
			->whereSid($sid)
			->update($properties);

		if($affected === 0) {
			self::getCache()->delete(self::$cachePrefix.$sid);
		} else {
			self::getCache()->set(self::$cachePrefix.$sid, $content, self::REGENERATION);
		}

		return TRUE;

	}

	/**
	 * Kill a session
	 */
	public static function kill(string $sid): bool {

		if($sid === NULL) {
			return FALSE;
		}

		Session::model()
			->whereSid($sid)
			->delete();

		self::getCache()->delete(self::$cachePrefix.$sid);

		return TRUE;
	}

	/**
	 * Kill all sessions for a given user
	 */
	public static function killByUser(\user\User $eUser): void {

		$cSession = Session::model()
			->select('sid')
			->union()
			->whereUser($eUser)
			->getCollection();

		foreach($cSession as $eSession) {
			self::kill($eSession['sid']);
		}

	}

	/**
	 * Kill all sessions for a given group of users.
	 */
	public static function killByUsers(array $users): void {

		$cSession = Session::model()
			->select('sid')
			->union()
			->whereUser('IN', $users)
			->getCollection();

		foreach($cSession as $eSession) {
			self::kill($eSession['sid']);
		}

	}

	/**
	 * Kill all active sessions
	 */
	public static function killAll(): void {

		Session::model()
			->all()
			->delete();

		self::getCache()->flush();

	}

	public static function getCache(): \Cache {

		if(self::$cache === NULL) {
			self::$cache = new \EmptyCache();
		}

		return self::$cache;

	}

	public static function setCache(\Cache $cache): void {
		self::$cache = $cache;
	}

	public static function setCachePrefix(string $prefix): void {
		self::$cachePrefix = $prefix;
	}

}
?>
