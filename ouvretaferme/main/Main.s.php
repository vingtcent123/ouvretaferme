<?php
namespace main;

class MainSetting extends \Settings {

	const MAINTENANCE = FALSE;

	const LIMIT_TRAINING = '2025-01-10';

	public static ?\user\User $onlineUser = NULL;
	public static array $backupServer = ['user' => NULL, 'hostname' => NULL];
	public static array $crypt = [];

	public static ?int $onlineSeason = NULL;

}

?>
