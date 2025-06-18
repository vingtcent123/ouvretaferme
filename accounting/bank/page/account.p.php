<?php
new \bank\AccountPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = REQUEST('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canView');
		\company\CompanyLib::connectSpecificDatabaseAndServer($data->eCompany);

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
	}
)
	->get('index', function($data) {

		$data->cAccount = \bank\AccountLib::getAll();
		throw new ViewAction($data);

	});

new \bank\AccountPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = REQUEST('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
		\company\CompanyLib::connectSpecificDatabaseAndServer($data->eCompany);

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
	}
)
	->quick(['label']);
?>
