<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->get('index', function($data) {

		$data->resultData = \overview\IncomeStatementLib::getResultOperationsByFinancialYear($data->eFinancialYear);
		$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->eFinancialYear);

		$threeNumbersClasses = array_merge(array_keys($data->resultData['expenses']), array_keys($data->resultData['incomes']));
		$twoNumbersClasses = array_map(fn($class) => (int)substr($class, 0, 2), $threeNumbersClasses);
		$classes = array_unique(array_merge($threeNumbersClasses, $twoNumbersClasses));

		$data->cAccount = \account\AccountLib::getByClasses($classes, 'class');

		throw new ViewAction($data);

	});
?>
