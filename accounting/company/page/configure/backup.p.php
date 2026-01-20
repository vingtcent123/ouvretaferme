<?php
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select('id')
			->whereHasAccounting(TRUE)
			->getCollection();

		// Extract credentials
		$username = GET('username');
		$password = GET('password');

		$date = date('Y-m-d');

		$mysqlBackupDir = \main\BackupLib::LOCAL_BACKUP_DIR.'mysql-backup/';

		foreach($cFarm as $eFarm) {

			$database = \farm\FarmSetting::getDatabaseName($eFarm);
			exec('mysqldump -u '.$username.' "'.$password.'" '.$database.' > '.$mysqlBackupDir.$date.'-'.$database.'.sql');
			exec('cp '.$mysqlBackupDir.$date.'-'.$database.'.sql '.$mysqlBackupDir.'backup/'.$database.'.sql');

		}

		exec('tar czf '.\main\BackupLib::LOCAL_BACKUP_DIR.\main\BackupLib::LOCAL_FARMS_BACKUP_FILE.' '.$mysqlBackupDir.'backup/');

		\main\BackupLib::backupFarms();

	});
?>
