<?php
/**
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260311
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260311
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

			$eVatAccount = \account\AccountLib::getByClass('44571');
			\account\Account::model()
				->whereClass('70')
				->update(['vatAccount' => $eVatAccount]);

			\account\FinancialYear::model()
				->whereAccountingMode(\account\FinancialYear::CASH_RECEIPTS)
				->update(['accountingType' => \account\FinancialYear::CASH]);

		}
	});
?>
