<?php
new Page()
	->remote('index', 'accounting', function($data) {

		$data->type = GET('type');
		if(in_array($data->type, [\account\Pdf::FINANCIAL_YEAR_OPENING, \account\Pdf::FINANCIAL_YEAR_CLOSING]) === FALSE) {
			throw new VoidAction();
		}

		if($data->type === \account\Pdf::FINANCIAL_YEAR_OPENING) {
			$eFinancialYear = \account\FinancialYearLib::getPreviousFinancialYear($data->eFarm['eFinancialYear']);
			if($eFinancialYear->empty()) {
				return new VoidAction();
			}
			$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);
		} else {
			$eFinancialYear = $data->eFarm['eFinancialYear'];
			$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);
		}


		[$data->balanceSheetData, $data->totals] = \overview\BalanceSheetLib::getData(
			eFinancialYear: $eFinancialYear,
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
