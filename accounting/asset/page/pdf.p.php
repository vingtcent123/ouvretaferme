<?php
new Page()
	->remote('amortization', 'accounting', function($data) {

		$data->assetAmortizations = \asset\AmortizationLib::getByFinancialYear($data->eFarm['eFinancialYear'], 'asset');
		$data->grantAmortizations = \asset\AmortizationLib::getByFinancialYear($data->eFarm['eFinancialYear'], 'grant');

		throw new ViewAction($data);

	})
	->remote('acquisition', 'accounting', function($data) {

		$data->cAsset = asset\AssetLib::getAcquisitions($data->eFarm['eFinancialYear'], 'asset');
		$data->cAssetGrant = asset\AssetLib::getAcquisitions($data->eFarm['eFinancialYear'], 'grant');

		throw new ViewAction($data);

	})
;
