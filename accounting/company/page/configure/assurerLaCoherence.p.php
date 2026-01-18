<?php
/**
 * À appeler comme ça : php framework/lime.php -a ouvretaferme -e prod company/configure/assurerLaCoherence
 */
new Page()
	->cli('index', function($data) {

		$eJournalCode = \company\JournalCode::model()
			->select(\company\JournalCode::getSelection())
			->whereCode(\journal\JournalSetting::JOURNAL_CODE_STOCK)
			->get();

		\company\GenericAccount::model()
			->whereClass('LIKE', '3%')
			->update(['journalCode' => $eJournalCode]);

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\company\CompanyLib::connectDatabase($eFarm);

			$eJournalCode = \journal\JournalCode::model()
				->select(\company\JournalCode::getSelection())
				->whereCode(\journal\JournalSetting::JOURNAL_CODE_STOCK)
				->get();

			\account\Account::model()
				->whereClass('LIKE', '3%')
				->update(['journalCode' => $eJournalCode]);

		}

	});
?>
