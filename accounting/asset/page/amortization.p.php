<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

})
	->get('/immobilisations', function($data) {

		$data->eFarm->validate('canManage');

		$selectedTab = GET('tab', 'string', 'asset');
		if(in_array($selectedTab, ['asset', 'grant']) === FALSE) {
			$selectedTab = 'asset';
		}
		$data->amortizations = \asset\AmortizationLib::getByFinancialYear($data->eFarm['eFinancialYear'], $selectedTab);
		$data->nAmortizations = \asset\AmortizationLib::countByFinancialYear($data->eFarm['eFinancialYear']);

		$data->nOperationMissingAsset = \asset\AssetLib::countOperationMissingAsset($data->eFarm['eFinancialYear']);

		if(count($data->amortizations) > 0 or $data->nOperationMissingAsset > 0) {
			$data->hasAsset = TRUE;
		} else {
			$data->hasAsset = \asset\AssetLib::hasAssets();
		}

		$data->selectedTab = $selectedTab;

		$data->eFinancialYearDocument = \account\FinancialYearDocumentLib::getDocument($data->eFarm['eFinancialYear'], \account\FinancialYearDocumentLib::ASSET_AMORTIZATION);

		$data->view = 'assets';

		throw new ViewAction($data);

	})
	->get('/immobilisations/acquisitions', function($data) {

		$data->cAsset = asset\AssetLib::getAcquisitions($data->eFarm['eFinancialYear'], 'asset');
		$data->cAssetSubvention = asset\AssetLib::getAcquisitions($data->eFarm['eFinancialYear'], 'grant');

		$data->nOperationMissingAsset = \asset\AssetLib::countOperationMissingAsset($data->eFarm['eFinancialYear']);

		$data->eFinancialYearDocument = \account\FinancialYearDocumentLib::getDocument($data->eFarm['eFinancialYear'], \account\FinancialYearDocumentLib::ASSET_ACQUISITION);

		$data->view = 'acquisitions';

		throw new ViewAction($data);

	});
