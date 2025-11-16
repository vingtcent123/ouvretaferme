<?php
new Page()
	->get('/asset/amortization', function($data) {

		$data->eFarm->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$selectedTab = GET('tab', 'string', 'asset');
		if(in_array($selectedTab, ['asset', 'grant']) === FALSE) {
			$selectedTab = 'asset';
		}
		$data->amortizations = \asset\AmortizationLib::getByFinancialYear($data->eFinancialYear, $selectedTab);

		$data->selectedTab = $selectedTab;

		throw new ViewAction($data);

	});
