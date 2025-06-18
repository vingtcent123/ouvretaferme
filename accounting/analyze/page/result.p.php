<?php
new Page(
	function ($data) {

		$data->eCompany = \company\CompanyLib::getById(REQUEST('company'))->validate('canView');

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
		\accounting\FinancialYearLib::checkHasAtLeastOne($data->cFinancialYear, $data->eCompany);

	}
)
	->get('index', function($data) {

		Setting::set('main\viewAnalyze', 'result');

		$data->cOperation = \journal\AnalyzeLib::getResultOperationsByMonth($data->eFinancialYear);
		[$data->result, $data->cAccount] = \journal\AnalyzeLib::getResult($data->eFinancialYear);

		throw new ViewAction($data);

	});

?>
