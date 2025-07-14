<?php
new \journal\AccruedIncomePage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	}
)
->create(function($data) {

	$data->e['date'] = $data->eFinancialYear['endDate'];

	throw new ViewAction($data);

})
->doCreate(function($data) {

	\account\LogLib::save('create', 'accruedIncome', ['id' => $data->e['id']]);

	throw new ReloadAction('journal', 'AccruedIncome::created');

})
->doDelete(function($data) {

	\account\LogLib::save('delete', 'accruedIncome', ['id' => $data->e['id']]);

	throw new ReloadAction('journal', 'AccruedIncome::deleted');

});
