<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$data->search = new Search([
		'precision' => GET('precision'),
		'view' => GET('view', default: \overview\IncomeStatementLib::VIEW_BASIC),
		'financialYearComparison' => GET('financialYearComparison'),
	], GET('sort'));

})
	->get('index', function($data) {

		if($data->search->get('financialYearComparison')) {
			$data->eFinancialYearComparison = \account\FinancialYearLib::getById($data->search->get('financialYearComparison'));
		} else {
			$data->eFinancialYearComparison = new \account\FinancialYear();
		}

		$data->resultData = \overview\IncomeStatementLib::getResultOperationsByFinancialYear(
			eFinancialYear: $data->eFinancialYear,
			isDetailed: $data->search->get('view') === \overview\IncomeStatementLib::VIEW_DETAILED,
			eFinancialYearComparison: $data->eFinancialYearComparison
		);

		$threeNumbersClasses = array_merge(
			array_map(fn($data) => (int)$data['class'], $data->resultData['expenses']['operating']),
			array_map(fn($data) => (int)$data['class'], $data->resultData['expenses']['financial']),
			array_map(fn($data) => (int)$data['class'], $data->resultData['expenses']['exceptional']),
			array_map(fn($data) => (int)$data['class'], $data->resultData['incomes']['operating']),
			array_map(fn($data) => (int)$data['class'], $data->resultData['incomes']['financial']),
			array_map(fn($data) => (int)$data['class'], $data->resultData['incomes']['exceptional']),
		);
		$twoNumbersClasses = array_map(fn($class) => (int)substr($class, 0, 2), $threeNumbersClasses);
		$classes = array_unique(array_merge($threeNumbersClasses, $twoNumbersClasses));

		$data->cAccount = \account\AccountLib::getByClasses($classes, 'class');

		throw new ViewAction($data);

	});
?>
