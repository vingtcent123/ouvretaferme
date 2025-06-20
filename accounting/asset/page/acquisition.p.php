<?php
new Page()
	->get('index', function($data) {

		\Setting::set('main\viewAsset', 'acquisition');

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		// TODO Récupérer et sauvegarder dynamiquement
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$data->cAsset = asset\AssetLib::getAcquisitions($data->eFinancialYear, 'asset');
		$data->cAssetSubvention = asset\AssetLib::getAcquisitions($data->eFinancialYear, 'subvention');

		throw new ViewAction($data);

	});
?>
