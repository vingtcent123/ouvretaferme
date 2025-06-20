<?php
new Page()
	->get('index', function($data) {

		\Setting::set('main\viewAsset', 'depreciation');

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		// TODO Récupérer et sauvegarder dynamiquement
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$data->assetDepreciations = \asset\DepreciationLib::getByFinancialYear($data->eFinancialYear, 'asset');
		$data->subventionDepreciations = \asset\DepreciationLib::getByFinancialYear($data->eFinancialYear, 'subvention');

		throw new ViewAction($data);

	});
