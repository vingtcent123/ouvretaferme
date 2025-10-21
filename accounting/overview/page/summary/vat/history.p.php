<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$data->search = new Search([
		'view' => GET('view', 'string', \overview\BalanceSheetLib::VIEW_BASIC),
		'financialYearComparison' => GET('financialYearComparison'),
	], GET('sort'));


})
	->get('index', function($data) {


		throw new ViewAction($data);

	});
?>
