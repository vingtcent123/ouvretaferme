<?php
/**
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrateInvoiceCashflow
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
     ->select(\farm\Farm::getSelection())
     ->whereHasAccounting(TRUE)
     ->whereId(GET('farm'), if: get_exists('farm'))
     ->getCollection();

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$cCashflow = \bank\Cashflow::model()
				->select('id', 'invoice')
				->whereInvoice('!=', NULL)
				->getCollection();

			foreach($cCashflow as $eCashflow) {

				\selling\Invoice::model()->update($eCashflow['invoice'], ['cashflow' => $eCashflow]);
			}

		}

	});
?>
