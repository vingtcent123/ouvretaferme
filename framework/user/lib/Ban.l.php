<?php

namespace user;

/**
 * User bannishement related methods.
 */
class BanLib extends BanCrud {

	/**
	 * Get the Ban element if the user or the IP provided is under bannishement.
	 * Return NULL otherwise.
	 */
	public static function getByUser(User $eUser, $ip = NULL) {

		$eUser->expects(['id']);

		if($ip === NULL) {
			$ip = \user\UserLib::getLastKnownIp($eUser);
		}

		$condition = "(`user` = ".$eUser['id'];

		if($ip !== NULL) {
			$condition .= " OR `ip` = ".Ban::model()->encode('ip', $ip);
		}

		$condition .= ") AND (`until` IS NULL OR `until` > NOW())";

		return Ban::model()
			->select(['id', 'user', 'ip', 'reason', 'until'])
			->where($condition)
			// In case the user is banned for multiple reasons at the same time we return the longest ban.
			->sort(['until' => SORT_DESC])
			->get();

	}

	/**
	 * Check if a user is banned right now.
	 * (True if is banned by IP or by user id)
	 * if $ip is null IP is automatically retrieved.
	 */
	public static function isBanned(User $eUser, string $ip = NULL): bool {

		return (self::getByUser($eUser, $ip)->empty() === FALSE);

	}

	/**
	 * Get the Ban element if the IP provided is under bannishement.
	 * Return NULL otherwise.
	 */
	public static function getByIp(string $ip) {

		return Ban::model()
			->select(['id', 'user', 'ip', 'reason', 'until'])
			->where("
				(`ip` = ".Ban::model()->encode('ip', $ip).") AND
				(`until` IS NULL OR `until` > NOW())"
			)
			// In case the user is banned for multiple reasons at the same time we return the longest ban.
			->sort(['until' => SORT_DESC])
			->get();

	}

	/**
	 * Do create a banishment for either a user, an IP or both.
	 */
	public static function createBan(User $eUser, User $eUserAdmin, array $type, string $reason, int $duration, \FailWatch $fw) {

		// Check parameters
		if($eUser->empty()) {
			throw new \NotExistsAction('User banned');
		}

		$eUser->expects(['id']);

		$banByUser = in_array('user', $type);
		$banByIp = in_array('ip', $type);

		if($banByUser === FALSE and $banByIp === FALSE) {
			Ban::fail('type[].empty');
		}

		if($eUserAdmin->empty()) {
			throw new \NotExistsAction('User who bans');
		}

		$eUserAdmin->expects(['id']);

		if(empty($reason)) {
			Ban::fail('reason.empty');
		}

		if(!is_int($duration) or $duration < -1) {
			throw new \NotExpectedAction('Duration parameter');
		}

		$userIp = \user\UserLib::getLastKnownIp($eUser);

		// Should not happen
		if($banByIp and $userIp === NULL) {
			throw new \NotExpectedAction('Can\'t ban by IP when IP is impossible to determine');
		}

		// Parameters are ok we create the bannishment
		if($fw->ok()) {

			$eBan = new Ban([
				'admin' => $eUserAdmin,
				'reason' => $reason,
				'ip' => NULL,
				'user' => NULL
			]);

			if($banByUser) {
				$eBan['user'] = $eUser;
			}

			if($banByIp) {
				$eBan['ip'] = $userIp;
			}

			// Select ban duration
			if($duration < 0) {
				$eBan['until'] = NULL;
			} else {
				$eBan['until'] = new \Sql('NOW() + INTERVAL '.$duration.' DAY');
			}

			// Add the ban in db
			Ban::model()->insert($eBan);

			// Destroy all sessions of this user
			if($banByUser) {
				\session\SessionLib::killByUser($eUser);
			}

			// Destroy all sessions for all users logged in with this IP in case we ban by IP address
			if($banByIp) {
				$users = \user\UserLib::getByIp($eBan['ip']);
				\session\SessionLib::killByUsers($users);
			}

		}

	}

	/**
	 * Change the end of a ban for $duration days to come.
	 * If $duration is negatif then the ban will last indefinitly.
	 * If $duration is 0 then the banishment ends right now.
	 */
	public static function changeBanDuration(Ban $eBan, int $duration) {

		if(!is_int($duration)) {
			trigger_error('Duration parameter must be an integer value');
		}

		if($duration < 0) {
			$eBan['until'] = NULL;
		} else {
			$eBan['until'] = new \Sql('NOW() + INTERVAL '.$duration.' DAY');
		}

		Ban::model()
			->select('until')
			->where("`until` > NOW() OR `until` IS NULL")
			->update($eBan);

	}

	/**
	 * Retrieve all active or ended banishment.
	 */
	public static function getAll(bool $active, int $page, User $eUser) {

		Ban::model()->select([
			'id',
			'user' => ['id', 'email'],
			'ip',
			'reason',
			'admin' => ['id', 'email', 'firstName', 'lastName', 'visibility'],
			'since',
			'until'
		]);

		if($active) {
			Ban::model()->where("`until` > NOW() OR `until` IS NULL");
		} else {
			Ban::model()->where("`until` <= NOW()");
		}

		if($eUser->empty() === FALSE) {
			Ban::model()->whereUser($eUser);
		}

		$cBan = Ban::model()
			->sort(['since' => SORT_DESC])
			->option('count')
			->getCollection($page * \Setting::get('user\maxByPage'), \Setting::get('user\maxByPage'));

		$nBan = Ban::model()->found();
		$nPage = ceil($nBan / \Setting::get('user\maxByPage'));

		return [
			$cBan,
			$nPage
		];

	}

}
?>
