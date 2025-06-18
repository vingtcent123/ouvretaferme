<?php

new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canView');

	[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
	\Setting::set('main\viewOverview', 'accounting');

})
	->get('index', function($data) {

		$data->accountingBalanceSheet = \overview\AccountingLib::getAccountingBalanceSheet($data->eFinancialYear);
		$data->summaryAccountingBalance = \overview\AccountingLib::getSummaryAccountingBalance($data->accountingBalanceSheet);

		throw new \ViewAction($data);
	});
?>
