<?php
new \company\CompanyPage()
	->applyElement(function($data, \company\Company $e) {
		$e->validate('canManage');
	})
	->update(function($data) {

		$data->eCompany = $data->e;
		\company\EmployeeLib::register($data->e);

		throw new ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('company', 'Company::updated'))
	->write('doClose', function($data) {

		$data->e['status'] = \company\Company::CLOSED;

		\company\CompanyLib::update($data->e, ['status']);

		throw new RedirectAction('/?success=company:Company::closed');

	});

(new \company\CompanyPage())
	->get('configuration', function($data) {

		throw new ViewAction($data, ':configuration');

	});
?>
