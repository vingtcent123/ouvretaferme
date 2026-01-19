<?php
/**
 * Script pour rajouter un compte manquant.
 * php framework/lime.php -a ouvretaferme -e prod company/configure/accounts
 */
new Page()
	->cli('index', function($data) {

		$class = '33';
		$eGenericAccount = \company\GenericAccountLib::getByClass($class);

		if($eGenericAccount->notEmpty()) {
			return;
		}

		$eJournalCode = \company\JournalCode::model()
			->select(\company\JournalCode::getSelection())
			->whereCode(\journal\JournalSetting::JOURNAL_CODE_STOCK)
			->get();

		$eGenericAccount = new \company\GenericAccount([
			'id' => 233,
			'class' => $class,
			'journalCode' => $eJournalCode,
			'description' => 'En-cours de production de biens',
			'type' => \company\GenericAccount::AGRICULTURAL,
		]);

		\company\GenericAccount::model()->insert($eGenericAccount);

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectDatabase($eFarm);

			$eAccount = \account\AccountLib::getByClass($class);

			if($eAccount->notEmpty()) {

				d('Farm #'.$eFarm['id'].' already has an account with class '.$class.' (#'.$eAccount['id'].')');

			} else {

				$eJournalCode = \journal\JournalCode::model()
					->select(\company\JournalCode::getSelection())
					->whereCode(\journal\JournalSetting::JOURNAL_CODE_STOCK)
					->get();


				$eGenericAccount['createdBy'] = new \user\User(['id' => 21]);
				$eGenericAccount['journalCode'] = $eJournalCode;
				\account\Account::model()->insert($eGenericAccount);
				\account\Account::model()
					->whereClass($class)
					->update(['id' => $eGenericAccount['id']]);

			}

		}

	});
?>
