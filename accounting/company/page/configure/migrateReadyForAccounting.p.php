<?php
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
     ->select(\farm\Farm::getSelection())
     ->whereHasAccounting(TRUE)
     ->whereId(GET('farm'), if: get_exists('farm'))
     ->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

			$cInvoice = \selling\Invoice::model()
				->select(\selling\Invoice::getSelection())
				->whereFarm($eFarm)
				->getCollection();

			foreach($cInvoice as $eInvoice) {
				if(\selling\InvoiceLib::isReadyForAccounting($eInvoice)) {
					\selling\Invoice::model()->update($eInvoice, ['readyForAccounting' => TRUE]);
				}
			}

			$cSale = \selling\Sale::model()
				->select(\selling\Sale::getSelection())
				->whereFarm($eFarm)
				->whereProfile('IN', [\selling\Sale::SALE, \selling\Sale::MARKET])
				->getCollection();

			foreach($cSale as $eSale) {
				if(\selling\SaleLib::isReadyForAccounting($eSale)) {
					\selling\Sale::model()->update($eSale, ['readyForAccounting' => TRUE]);
				}
			}

		}

	});
?>
