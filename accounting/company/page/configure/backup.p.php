<?php
new Page()
	->cli('index', function($data) {

		$cCompany = \company\CompanyLib::getList();

		// Extract credentials
		$username = GET('username');
		$password = GET('password');

		$date = date('Y-m-d');

		$mysqlBackupDir = \main\BackupLib::LOCAL_BACKUP_DIR.'mysql-backup/';

		foreach($cCompany as $eCompany) {

			$database = \company\CompanyLib::getDatabaseName($eCompany['farm']);
			exec('mysqldump -u '.$username.' "'.$password.'" '.$database.' > '.$mysqlBackupDir.$date.'-'.$database.'.sql');
			exec('cp '.$mysqlBackupDir.$date.'-'.$database.'.sql '.$mysqlBackupDir.'backup/'.$database.'.sql');

		}

		exec('tar czf '.\main\BackupLib::LOCAL_BACKUP_DIR.\main\BackupLib::LOCAL_FARMS_BACKUP_FILE.' '.$mysqlBackupDir.'backup/');

		\main\BackupLib::backupFarms();

	});
?>
