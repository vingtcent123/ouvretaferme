<?php
new Page()
	->remote('balance', 'accounting', function($data) {

		if(in_array(GET('type'), [\account\FinancialYearDocumentLib::BALANCE, \account\FinancialYearDocumentLib::BALANCE_DETAILED]) === FALSE) {
			throw new VoidAction();
		}

		if(GET('type') === \account\FinancialYearDocumentLib::BALANCE_DETAILED) {
			$precision = 8;
		} else {
			$precision = 2;
		}

		$searchCurrent = new Search([
			'startDate' => $data->eFarm['eFinancialYear']['startDate'],
			'endDate' => date('Y-m-d', strtotime($data->eFarm['eFinancialYear']['endDate'])),
			'summary' => GET('summary'), 'precision' => $precision,
		]);
		$searchPrevious = new Search([
			'startDate' => date('Y-m-d', strtotime($data->eFarm['eFinancialYear']['startDate'].' - 1 YEAR')),
			'endDate', date('Y-m-d', strtotime($data->eFarm['eFinancialYear']['endDate'].' - 1 YEAR')),
			'summary' => GET('summary'), 'precision' => $precision,
		]);

		$data->search = new Search(['summary' => GET('summary'), 'precision' => $precision]);
		$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->eFarm['eFinancialYear']);

		$data->trialBalanceData = \journal\TrialBalanceLib::extractByAccounts($searchCurrent, $data->eFarm['eFinancialYear']);

		if($data->eFinancialYearPrevious->notEmpty()) {

			$data->trialBalancePreviousData = \journal\TrialBalanceLib::extractByAccounts($searchPrevious, $data->eFinancialYearPrevious);

		} else {

			$data->trialBalancePreviousData = [];

		}

		throw new ViewAction($data);

	})
;
