<?php
new Page()
	->remote('balance', 'accounting', function($data) {

		$searchCurrent = new Search([
			'startDate' => $data->eFarm['eFinancialYear']['startDate'],
			'endDate' => date('Y-m-d', strtotime($data->eFarm['eFinancialYear']['endDate'])),
		]);
		$searchPrevious = new Search([
			'startDate' => date('Y-m-d', strtotime($data->eFarm['eFinancialYear']['startDate'].' - 1 YEAR')),
			'endDate', date('Y-m-d', strtotime($data->eFarm['eFinancialYear']['endDate'].' - 1 YEAR')),
		]);

		$data->search = new Search(['summary' => GET('summary')]);
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
