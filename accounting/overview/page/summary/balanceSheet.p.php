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

		if($data->search->get('financialYearComparison')) {
			$data->eFinancialYearComparison = \account\FinancialYearLib::getById($data->search->get('financialYearComparison'));
		} else {
			$data->eFinancialYearComparison = new \account\FinancialYear();
		}

		[$data->balanceSheetData, $data->totals] = \overview\BalanceSheetLib::getData(
			eFinancialYear: $data->eFinancialYear,
			eFinancialYearComparison: $data->eFinancialYearComparison,
			isDetailed: $data->search->get('view') === \overview\BalanceSheetLib::VIEW_DETAILED
		);

		$classes = [];
		foreach($data->balanceSheetData as $category => $list) {
			$classes = array_unique(array_merge($classes, array_map(fn($element) => $element['class'], $list)));
		}
		$twoNumbersClasses = array_map(fn($class) => substr($class, 0, 2), $classes);
		// Si certaines classes exactes existent (pour le détail), les prendre
		$completeNumbersClasses = array_map(fn($class) => trim($class, '0'), $classes);
		$classes = array_unique(array_merge(
			$twoNumbersClasses, $completeNumbersClasses,
			[\account\AccountSetting::PROFIT_CLASS, \account\AccountSetting::LOSS_CLASS, \account\AccountSetting::RESULT_CLASS])
		);

		$data->cAccount = \account\AccountLib::getByClasses($classes, 'class');

		throw new ViewAction($data);

	});
?>
