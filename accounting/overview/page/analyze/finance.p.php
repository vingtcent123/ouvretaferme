<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->get(['index'], function($data) {

		$data->ccOperationBank = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'bank');
		$data->ccOperationCash = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'cash');

		throw new ViewAction($data);


	});
?>
