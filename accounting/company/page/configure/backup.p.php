<?php
new Page()
	->cli('index', function($data) {

		$cCompany = \company\CompanyLib::getList();

		// Extract credentials
		$username = GET('username');
		$password = GET('password');

		$date = date('Y-m-d');

		exec('mysqldump -u '.$username.' "'.$password.'" comptabilite > /var/www/mysql-backup/'.$date.'-comptabilite.sql');
		exec('cp /var/www/mysql-backup/'.$date.'-comptabilite.sql /var/www/mysql-backup/backup/comptabilite.sql');
		foreach($cCompany as $eCompany) {

			$database = \company\CompanyLib::getDatabaseName($eCompany['farm']);
			exec('mysqldump -u '.$username.' "'.$password.'" '.$database.' > /var/www/mysql-backup/'.$date.'-'.$database.'.sql');
			exec('cp /var/www/mysql-backup/'.$date.'-'.$database.'.sql /var/www/mysql-backup/backup/'.$database.'.sql');

		}

		exec('tar czf /var/www/mpf-mysql.tar.gz /var/www/mysql-backup/backup/');

		\main\BackupLib::backupMpf();

	});
?>
