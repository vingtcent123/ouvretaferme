<?php
namespace main;

class MainSetting extends \Settings {

	const MAINTENANCE = FALSE;

	const LIMIT_TRAINING = '2025-01-10';

	const URL_PHOTOS = 'https://www.dropbox.com/scl/fo/z3nkaufqkik90d0xs0b6q/h?rlkey=b69944c6ui40ck22gi6lk2imb&dl=0';

	public static ?\user\User $onlineUser = NULL;
	public static array $backupServer = ['user' => NULL, 'hostname' => NULL];
	public static array $crypt = [];

	public static ?int $onlineSeason = NULL;

}

?>
