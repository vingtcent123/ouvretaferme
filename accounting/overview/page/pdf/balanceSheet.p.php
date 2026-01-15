<?php
new Page()
	->remote('index', 'accounting', function($data) {

		$data->type = GET('type');
		if(in_array($data->type, [\account\FinancialYearDocumentLib::BALANCE_SHEET, \account\FinancialYearDocumentLib::OPENING, \account\FinancialYearDocumentLib::OPENING_DETAILED, \account\FinancialYearDocumentLib::CLOSING, \account\FinancialYearDocumentLib::CLOSING_DETAILED]) === FALSE) {
			throw new VoidAction();
		}

		if($data->type === \account\FinancialYearDocumentLib::OPENING or $data->type === \account\FinancialYearDocumentLib::OPENING_DETAILED) {
			$eFinancialYear = \account\FinancialYearLib::getPreviousFinancialYear($data->eFarm['eFinancialYear']);
			if($eFinancialYear->empty()) {
				return new VoidAction();
			}
			$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);
			if($data->type === \account\FinancialYearDocumentLib::OPENING_DETAILED) {
				$isDetailed = TRUE;
			} else {
				$isDetailed = FALSE;
			}
		} else {
			$eFinancialYear = $data->eFarm['eFinancialYear'];
			$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);
			if($data->type === \account\FinancialYearDocumentLib::CLOSING_DETAILED) {
				$isDetailed = TRUE;
			} else {
				$isDetailed = FALSE;
			}
		}


		[$data->balanceSheetData, $data->totals] = \overview\BalanceSheetLib::getData(
			eFinancialYear: $eFinancialYear,
			eFinancialYearComparison: $eFinancialYearPrevious,
			isDetailed: $isDetailed
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

		$data->isDetailed = $isDetailed;

		throw new ViewAction($data);

	});
?>
