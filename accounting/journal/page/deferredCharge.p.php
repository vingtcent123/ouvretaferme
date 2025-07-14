<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
->get('set', function($data) {

	$data->eFinancialYear = \account\FinancialYearLib::getById(GET('financialYear'))->validate('canUpdate');
	$data->eOperation = \journal\OperationLib::getById(GET('operation'));

	if($data->eOperation->isDeferrableCharge($data->eFinancialYear) === FALSE) {
		throw new NotExpectedAction('Cannot defer charge on operation');
	}

	$data->field = GET('field');

	throw new ViewAction($data);

})
->post('doSet', function($data) {

	\journal\DeferredChargeLib::createDeferredCharge($_POST);

	throw new ReloadAction('journal', 'DeferredCharge::saved');

});

new \journal\DeferredChargePage(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
->doDelete(function($data) {

	\account\LogLib::save('delete', 'deferredCharge', ['id' => $data->e['id']]);

	throw new ReloadAction('journal', 'DeferredCharge::deleted');

});
