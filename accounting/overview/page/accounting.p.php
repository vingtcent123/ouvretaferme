<?php

new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');

	// TODO Récupérer et sauvegarder dynamiquement
	$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	\Setting::set('main\viewOverview', 'accounting');

})
	->get('index', function($data) {

		$data->accountingBalanceSheet = \overview\AccountingLib::getAccountingBalanceSheet($data->eFinancialYear);
		$data->summaryAccountingBalance = \overview\AccountingLib::getSummaryAccountingBalance($data->accountingBalanceSheet);

		throw new \ViewAction($data);
	});
?>
