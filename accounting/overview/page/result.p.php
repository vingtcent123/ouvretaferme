<?php
new Page(
	function ($data) {

		$data->eCompany = \company\CompanyLib::getById(REQUEST('company'))->validate('canView');

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
		\account\FinancialYearLib::checkHasAtLeastOne($data->cFinancialYear, $data->eCompany);

	}
)
	->get('index', function($data) {

		Setting::set('main\viewAnalyze', 'result');

		$data->cOperation = \overview\AnalyzeLib::getResultOperationsByMonth($data->eFinancialYear);
		[$data->result, $data->cAccount] = \overview\AnalyzeLib::getResult($data->eFinancialYear);

		throw new ViewAction($data);

	});

?>
