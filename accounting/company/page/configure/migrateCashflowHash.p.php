<?php
new Page()
	->get('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select('id')
			->whereHasAccounting(TRUE)
			->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);
			$cCashflow = \bank\CashflowLib::getAll(new Search(), FALSE);

			foreach($cCashflow as $eCashflow) {

				$eOperationCashflow = \journal\OperationCashflow::model()
					->select(['operation' => ['hash']])
					->whereCashflow($eCashflow)
					->get();

				if($eOperationCashflow->notEmpty()) {

					\bank\Cashflow::model()->update($eCashflow, ['hash' => $eOperationCashflow['operation']['hash']]);

				}
			}

		}

	});
?>
