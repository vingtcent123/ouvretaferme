<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->get(['index'], function($data) {

		$data->cOperation = \overview\AnalyzeLib::getResultOperationsByMonth($data->eFinancialYear);

		throw new ViewAction($data);

	});
?>
