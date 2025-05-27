<?php
/**
 * Backup
 *
 */
(new Page())
	->cron('index', function($data) {

		\main\BackupLib::backupDatabase();
		\main\BackupLib::backupStorage();

		\main\BackupLib::cleanDatabase();
		\main\BackupLib::cleanStorage();

	}, interval: '0 4 * * *');
?>
