<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
->get('set', function($data) {

	$data->eFinancialYear = \account\FinancialYearLib::getById(GET('financialYear'))->validate('canUpdate');
	$data->eOperation = \journal\OperationLib::getById(GET('operation'));

	if($data->eOperation->isDeferrable($data->eFinancialYear) === FALSE) {
		throw new NotExpectedAction('Cannot defer operation');
	}

	$data->field = GET('field');

	throw new ViewAction($data);

})
->post('doSet', function($data) {

	$success = \journal\DeferralLib::createDeferral($_POST);

	throw new ReloadAction('journal', $success);

});

new \journal\DeferralPage(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
->doDelete(function($data) {

	\account\LogLib::save('delete', 'Deferral', ['id' => $data->e['id']]);

	throw new ReloadAction('journal', 'Deferral::deleted');

});
