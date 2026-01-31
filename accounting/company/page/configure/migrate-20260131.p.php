<?php
/**
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260131
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260131
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
				if($eFinancialYear['hasVat']) {
					\account\FinancialYear::model()->update($eFinancialYear, ['vatChargeability' => \account\FinancialYear::CASH]);
				}
			}

		}

	});
?>
