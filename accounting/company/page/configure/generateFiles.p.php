<?php
/**
 * À appeler comme ça : php framework/lime.php -a ouvretaferme -e prod company/configure/generateFiles
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasFinancialYears(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\company\CompanyLib::connectDatabase($eFarm);

			$cFinancialYear = \account\FinancialYear::model()
				->select(\account\FinancialYear::getSelection())
				->whereStatus('closed')
				->getCollection();

			foreach($cFinancialYear as $eFinancialYear) {

				\account\FinancialYearDocumentLib::regenerateAll($eFarm, $eFinancialYear);
				echo '.';

			}

		}

	});
?>
