<?php
new Page()
	->remote('index', 'accounting', function($data) {

		$data->type = GET('type');
		if(in_array($data->type, [\account\FinancialYearDocumentLib::INCOME_STATEMENT, \account\FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED]) === FALSE) {
			throw new VoidAction();
		}
		$data->eFinancialYear = $data->eFarm['eFinancialYear'];
		$data->eFinancialYearComparison = \account\FinancialYearLib::getPreviousFinancialYear($data->eFinancialYear);

		$data->resultData = \overview\IncomeStatementLib::getResultOperationsByFinancialYear(
			eFinancialYear: $data->eFinancialYear,
			isDetailed: $data->type === \account\FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED,
			eFinancialYearComparison: $data->eFinancialYearComparison
		);

		if(count($data->resultData) > 0) {

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

		}

		throw new ViewAction($data);

	});
?>
