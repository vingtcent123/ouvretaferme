<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$search = new Search([
		'startDate' => GET('startDate'),
		'endDate' => GET('endDate'),
		'precision' => GET('precision'),
	], GET('sort'));

	$data->search = clone $search;

})
	->get('index', function($data) {

		$data->trialBalanceData = \journal\TrialBalanceLib::extractByAccounts($data->search, $data->eFinancialYear);

		throw new ViewAction($data);

	});
