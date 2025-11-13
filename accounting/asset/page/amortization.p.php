<?php
new Page()
	->get('/asset/amortization', function($data) {

		$data->eFarm->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$data->assetDepreciations = \asset\AmortizationLib::getByFinancialYear($data->eFinancialYear, 'asset');
		$data->subventionDepreciations = \asset\AmortizationLib::getByFinancialYear($data->eFinancialYear, 'subvention');

		throw new ViewAction($data);

	});
