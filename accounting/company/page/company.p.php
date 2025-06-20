<?php
new \farm\FarmPage()
	->applyElement(function($data, \farm\Farm $e) {
		$e->validate('canManage');
	})
	->update(function($data) {

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('company', 'Company::updated'))
	->write('doClose', function($data) {

		$data->e['status'] = \company\Company::CLOSED;

		\company\CompanyLib::update($data->e, ['status']);

		throw new RedirectAction('/?success=company:Company::closed');

	});

new \farm\FarmPage()
	->get('configuration', function($data) {

		throw new ViewAction($data, ':configuration');

	});
?>
