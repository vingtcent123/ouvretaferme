<?php
namespace user;

class UserSetting extends \Settings {

	public static bool $featureSignUp = TRUE;
	public static bool $featureBan = FALSE;

	const PASSWORD_SIZE_MIN = 8;
	const NAME_SIZE_MAX = 50;

	public static bool $checkTos = FALSE;
	public static array $signUpRoles = [];
	public static array $statsRoles = [];
	public static string $signUpView = '';

	// Number of days for saving logs
	const KEEP_LOGS = 90;

	// Number of minutes after login to close its account
	const CLOSE_TIME_LIMIT = 3;

	// Number of days to cancel account closing
	const CLOSE_TIMEOUT = 10;

	// Authorized authentication
	const AUTH = ['basic'];

	// Maximum allowed people on the same IP to allow banishment by IP
	const MAX_BAN_ON_SAME_IP = 1000;

	// Maximum ban displayed per page on ban admin page
	const MAX_BY_PAGE = 50;
	const LOG_SPLIT = 1;
}


UserSetting::setPrivilege('admin', FALSE);
UserSetting::setPrivilege('ban', FALSE);
UserSetting::setPrivilege('privilege', FALSE);

?>
