<?php
new \bank\BankAccountPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

		// TODO Récupérer et sauvegarder dynamiquement
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();
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

		// TODO Récupérer et sauvegarder dynamiquement
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();
	}
)
	->quick(['label']);
?>
