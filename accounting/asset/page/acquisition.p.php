<?php
new Page()
	->get('index', function($data) {

		$data->eFarm->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$data->cAsset = asset\AssetLib::getAcquisitions($data->eFinancialYear, 'asset');
		$data->cAssetSubvention = asset\AssetLib::getAcquisitions($data->eFinancialYear, 'subvention');

		throw new ViewAction($data);

	});
?>
