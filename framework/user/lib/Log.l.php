<?php
namespace user;

/**
 * Handle log connections for users
 */
class LogLib {

	/**
	 * Add a new entry in log database
	 */
	public static function add(User $eUser, string $action) {

		$eLog = new Log([
			'user' => $eUser,
			'action' => $action,
			'device' => \util\DeviceLib::get(),
			'deviceVersion' => \util\DeviceLib::version(),
		]);

		Log::model()->insert($eLog);

	}

	/**
	 * Clean old logs
	 */
	public static function clean() {

		Log::model()
			->where('createdAt < NOW() - INTERVAL '.\Setting::get('keepLogs').' DAY')
			->union()
			->delete();

	}

	/**
	 * Cleans old userAuto entries
	 *
	 */
	public static function cleanAuto() {

		UserAuto::model()
			->where('expiresAt < NOW() OR status = "'.UserAuto::DELETED.'"')
			->delete();
	}

}
?>
