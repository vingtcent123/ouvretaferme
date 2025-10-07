<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->get('index', function($data) {

		$data->cOperation = \overview\BalanceSheetLib::getData($data->eFinancialYear);

		$data->result = \overview\IncomeStatementLib::computeResult($data->eFinancialYear);

		$threeNumbersClasses = $data->cOperation->getColumn('class');
		$twoNumbersClasses = array_map(fn($class) => substr($class, 0, 2), $threeNumbersClasses);
		$classes = array_unique(array_merge($threeNumbersClasses, $twoNumbersClasses, [\account\AccountSetting::PROFIT_CLASS, \account\AccountSetting::LOSS_CLASS, \account\AccountSetting::RESULT_CLASS]));

		$data->cAccount = \account\AccountLib::getByClasses($classes, 'class');

		throw new ViewAction($data);

	});
?>
