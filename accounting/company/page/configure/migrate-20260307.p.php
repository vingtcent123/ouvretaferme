<?php
/**
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260307
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260307
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select('id')
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFinancialYear = \account\FinancialYearLib::getLastFinancialYear();

			if($eFinancialYear->notEmpty()) {
				\farm\Farm::model()
					->where(TRUE)
					->update(['legalCategory' => $eFinancialYear['legalCategory']]);
			}

		}
	});
?>
