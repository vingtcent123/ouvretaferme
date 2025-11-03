<?php
new Page()
	->get('/asset/depreciation', function($data) {

		$data->eFarm->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$data->assetDepreciations = \asset\DepreciationLib::getByFinancialYear($data->eFinancialYear, 'asset');
		$data->subventionDepreciations = \asset\DepreciationLib::getByFinancialYear($data->eFinancialYear, 'subvention');

		throw new ViewAction($data);

	});
