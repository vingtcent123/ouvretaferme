<?php
/**
 * Redresse les numéros de hash des écritures importées depuis les FEC
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260205
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260205
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);

			$cFinancialYear = \account\FinancialYearLib::getAll();

			foreach($cFinancialYear as $eFinancialYear) {

				if(\account\Import::model()->whereFinancialYear($eFinancialYear)->count() > 0) {
					continue;
				}

				if($eFinancialYear['closeDate'] !== NULL) {
					\journal\OperationLib::setNumbers($eFinancialYear);
				}

			}
		}

	});
?>
