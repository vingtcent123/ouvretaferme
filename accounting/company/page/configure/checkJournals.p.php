<?php
/**
 * php framework/lime.php -a ouvretaferme -e dev company/configure/checkJournals
 * php framework/lime.php -a ouvretaferme -e prod company/configure/checkJournals
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasFinancialYears(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		$do = GET('do', 'bool', FALSE);

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);

			$ccOperation = \journal\Operation::model()
				->select('hash', 'journalCode')
				->getCollection(index: ['hash', 'journalCode']);

			foreach($ccOperation as $hash => $cOperation) {

				$journals = [];
				foreach($cOperation as $eOperation) {
					$journals[] = $eOperation['journalCode']['id'] ?? NULL;
				}

				$journals = array_unique($journals);

				if(count($journals) > 1) {
					if(in_array(NULL, $journals)) {
						$has = ' dont NULL';
					} else {
						$has = '';
					}
					d($hash.' ('.count($journals).$has.')');
				} else {
					d('.');
				}

				if($do and count($journals) === 2 and in_array(NULL, $journals)) {

					$journal = array_find($journals, fn($value) => $value !== NULL);
					$eJournal = \journal\JournalCodeLib::getById($journal);

					if($eJournal->notEmpty()) {

						\journal\Operation::model()
							->whereHash($hash)
							->whereJournalCode(NULL)
							->update(['journalCode' => $eJournal]);

					}
				}

			}

		}

	});
