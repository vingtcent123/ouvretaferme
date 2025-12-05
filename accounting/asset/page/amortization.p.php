<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();
})
	->get('/immobilisations', function($data) {

		$data->eFarm->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$selectedTab = GET('tab', 'string', 'asset');
		if(in_array($selectedTab, ['asset', 'grant']) === FALSE) {
			$selectedTab = 'asset';
		}
		$data->amortizations = \asset\AmortizationLib::getByFinancialYear($data->eFinancialYear, $selectedTab);

		$data->selectedTab = $selectedTab;

		$data->view = 'assets';

		throw new ViewAction($data);

	})
	->get('/immobilisations/acquisitions', function($data) {

		$data->cAsset = asset\AssetLib::getAcquisitions($data->eFinancialYear, 'asset');
		$data->cAssetSubvention = asset\AssetLib::getAcquisitions($data->eFinancialYear, 'subvention');

		$data->view = 'acquisitions';

		throw new ViewAction($data);

	});
