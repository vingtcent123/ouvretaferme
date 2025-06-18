<?php
	new Page()
		->cli('index', function($data) {

			$cCompany = \company\CompanyLib::getList();

			// Extract credentials
			$username = GET('username');
			$password = GET('password');

			$date = date('Y-m-d');

			exec('mysqldump -u '.$username.' "'.$password.'" mapetiteferme > /var/www/mysql-backup/'.$date.'-mapetiteferme.sql');
			foreach($cCompany as $eCompany) {

				$database = \company\CompanyLib::getDatabaseName($eCompany);
				exec('mysqldump -u '.$username.' "'.$password.'" '.$database.' > /var/www/mysql-backup/'.$date.'-'.$database.'.sql');

			}

		});
?>
