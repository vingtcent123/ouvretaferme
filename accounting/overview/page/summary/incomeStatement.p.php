<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$data->search = new Search([
		'precision' => GET('precision'),
		'summary' => GET('summary'),
	], GET('sort'));

})
	->get('index', function($data) {

		$data->resultData = \overview\IncomeStatementLib::getResultOperationsByFinancialYear($data->eFinancialYear, (bool)$data->search->get('summary'));
		$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->eFinancialYear);

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
