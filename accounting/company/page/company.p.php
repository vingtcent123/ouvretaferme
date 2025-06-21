<?php
new \farm\FarmPage()
	->applyElement(function($data, \farm\Farm $e) {
		$e->validate('canManage');
	})
	->update(function($data) {

		throw new ViewAction($data);

	});
new \company\CompanyPage()
	->doUpdate(fn() => throw new ReloadAction('company', 'Company::updated'));

new \farm\FarmPage()
	->get('configuration', function($data) {

		throw new ViewAction($data, ':configuration');

	});
?>
