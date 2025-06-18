<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = INPUT('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canView');

		\Setting::set('main\viewBank', 'import');
	}
)
	->get('index', function($data) {

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

		$data->imports = \bank\ImportLib::formatCurrentFinancialYearImports($data->eFinancialYear);
		$data->cImport = \bank\ImportLib::getAll($data->eFinancialYear);

		throw new ViewAction($data);

	});

new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = INPUT('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');

		\Setting::set('main\viewBank', 'import');
	}
)
	->get('import', function($data) {

		throw new ViewAction($data);

	})
	->post('doImport', function($data) {

		$fw = new FailWatch();

		$result = \bank\ImportLib::importBankStatement($data->eCompany);

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::urlBank($data->eCompany).'/import?success=bank:Import::'.$result);
		} else {
			throw new RedirectAction(\company\CompanyUi::urlBank($data->eCompany).'/import:import?error='.$fw->getLast());
		}

	});
?>
