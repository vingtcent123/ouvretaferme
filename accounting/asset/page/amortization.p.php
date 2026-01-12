<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
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

		$data->view = 'assets';

		throw new ViewAction($data);

	})
	->get('/immobilisations/acquisitions', function($data) {

		$data->cAsset = asset\AssetLib::getAcquisitions($data->eFarm['eFinancialYear'], 'asset');
		$data->cAssetSubvention = asset\AssetLib::getAcquisitions($data->eFarm['eFinancialYear'], 'subvention');

		$data->view = 'acquisitions';

		throw new ViewAction($data);

	});
