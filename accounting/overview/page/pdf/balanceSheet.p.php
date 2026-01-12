<?php
new Page()
	->remote('index', 'accounting', function($data) {

		$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->eFarm['eFinancialYear']);

		[$data->balanceSheetData, $data->totals] = \overview\BalanceSheetLib::getData(
			eFinancialYear: $data->eFarm['eFinancialYear'],
			eFinancialYearComparison: $eFinancialYearPrevious,
			isDetailed: FALSE
		);

		if(count($data->balanceSheetData) > 0) {

			$classes = [];
			foreach($data->balanceSheetData as $list) {
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

		} else {

			$data->cAccount = new Collection();

		}

		throw new ViewAction($data);

	});
?>
