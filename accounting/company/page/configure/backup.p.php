<?php
new Page()
	->cli('index', function($data) {

		$cCompany = \company\CompanyLib::getList();

		// Extract credentials
		$username = GET('username');
		$password = GET('password');

		$date = date('Y-m-d');

		foreach($cCompany as $eCompany) {

			$database = \company\CompanyLib::getDatabaseName($eCompany['farm']);
			exec('mysqldump -u '.$username.' "'.$password.'" '.$database.' > /var/www/mysql-backup/'.$date.'-'.$database.'.sql');

		}

	});
?>
