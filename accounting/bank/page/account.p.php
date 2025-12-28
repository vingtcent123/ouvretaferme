<?php
new \bank\BankAccountPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	}
)
	->get('index', function($data) {

		$data->cBankAccount = \bank\BankAccountLib::getAll();
		throw new ViewAction($data);

	});

new \bank\BankAccountPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	}
)
	->quick(['label', 'description']);
?>
