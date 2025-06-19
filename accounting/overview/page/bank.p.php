<?php
new Page(
	function ($data) {

		$data->eCompany = \company\CompanyLib::getById(REQUEST('company'))->validate('canView');

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
		\account\FinancialYearLib::checkHasAtLeastOne($data->cFinancialYear, $data->eCompany);

	}
)
	->get('index', function($data) {

		Setting::set('main\viewAnalyze', 'bank');

		$data->cOperationBank = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'bank');
		$data->cOperationCash = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'cash');

		throw new ViewAction($data);

	});
