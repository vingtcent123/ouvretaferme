<?php
new Page()
	->cli('index', function($data) {

		$eFarm = \farm\FarmLib::getById(\association\AssociationSetting::FARM);

		\company\CompanyLib::connectDatabase($eFarm);

		// Copy Account content from package main to package accounting
		$cAccount = \company\GenericAccount::model()
			->select(\company\GenericAccount::getSelection())
			->whereType(\company\GenericAccount::ASSOCIATION)
			->getCollection();

		$eUser = \user\UserLib::getById(21);

		foreach($cAccount as $eAccount) {
			$eAccount['createdBy'] = $eUser;
			\account\Account::model()->insert($eAccount);
		}

	});
?>
