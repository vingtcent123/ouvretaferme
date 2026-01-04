<?php

new Page(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	$views = array_column(\farm\FarmUi::getAccountingFinancialsCategories(), 'fqn');

	if(get_exists('view') and in_array(GET('view'), $views)) {

		$view = array_find_key(\farm\FarmUi::getAccountingFinancialsCategories(), fn($category) => $category !== NULL and $category['fqn'] === GET('view'));
		$data->view = \farm\FarmerLib::setView('viewAccountingFinancials', $data->eFarm, $view);

	} else {

		$data->view = $data->eFarm->getView('viewAccountingFinancials');

	}

	if($data->eFarm['eFinancialYear']['hasVat'] === FALSE and $data->view === \farm\Farmer::VAT) {
		$data->view = \farm\FarmerLib::setView('viewAccountingFinancials', $data->eFarm, \farm\Farmer::BANK);
	}

})
	->get(['/etats-financiers/', '/etats-financiers/{view}'], function($data) {

		$data->search = new Search([
			'financialYearComparison' => GET('financialYearComparison'),
			'netOnly' => GET('netOnly', 'bool', FALSE)
		], GET('sort'));

		if($data->search->get('financialYearComparison')) {
			$data->eFinancialYearComparison = \account\FinancialYearLib::getById($data->search->get('financialYearComparison'));
		} else {
			$data->eFinancialYearComparison = new \account\FinancialYear();
		}

		switch($data->view) {

			case \farm\Farmer::BANK:
				$data->ccOperationBank = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFarm['eFinancialYear'], 'bank');
				$data->ccOperationCash = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFarm['eFinancialYear'], 'cash');
				break;

			case \farm\Farmer::CHARGES:
				[$data->cOperation, $data->cAccount] = \overview\AnalyzeLib::getChargeOperationsByMonth($data->eFarm['eFinancialYear']);
				$data->cOperationResult = \overview\AnalyzeLib::getResultOperationsByMonth($data->eFarm['eFinancialYear']);
				break;

			case \farm\Farmer::SIG:

				$data->search = new Search([
					'financialYearComparison' => GET('financialYearComparison'),
				], GET('sort'));

				$values = \overview\SigLib::compute($data->eFarm['eFinancialYear']);
				$data->values = [
					$data->eFarm['eFinancialYear']['id'] => $values
				];

				if($data->search->get('financialYearComparison') and (int)$data->search->get('financialYearComparison') !== $data->eFarm['eFinancialYear']['id']) {
					$data->eFinancialYearComparison = \account\FinancialYearLib::getById($data->search->get('financialYearComparison'));
					if($data->eFinancialYearComparison->notEmpty()) {
						$data->values[$data->eFinancialYearComparison['id']] = \overview\SigLib::compute($data->eFinancialYearComparison);
					}
				} else {
					$data->eFinancialYearComparison = new \account\FinancialYear();
				}
				break;

			case \farm\Farmer::BALANCE_SHEET:

				$data->search->set('type', GET('type', 'string', \overview\BalanceSheetLib::VIEW_BASIC));

				[$data->balanceSheetData, $data->totals] = \overview\BalanceSheetLib::getData(
					eFinancialYear: $data->eFarm['eFinancialYear'],
					eFinancialYearComparison: $data->eFinancialYearComparison,
					isDetailed: $data->search->get('type') === \overview\BalanceSheetLib::VIEW_DETAILED
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

				}
				break;

			case \farm\Farmer::INCOME_STATEMENT:

				$data->search->set('type', GET('type', 'string', \overview\IncomeStatementLib::VIEW_BASIC));

				$data->resultData = \overview\IncomeStatementLib::getResultOperationsByFinancialYear(
					eFinancialYear: $data->eFarm['eFinancialYear'],
					isDetailed: $data->search->get('type') === \overview\IncomeStatementLib::VIEW_DETAILED,
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

				break;

			case \farm\Farmer::VAT:

				$tab = GET('tab');
				if(in_array($tab, ['journal-sell', 'journal-buy', 'check', 'cerfa', 'history']) === FALSE) {
					$tab = NULL;
				}
				$data->tab = $tab;

				$search = new Search();
				$search->set('financialYear', $data->eFarm['eFinancialYear']);

				$data->vatParameters = \overview\VatLib::getDefaultPeriod($data->eFarm, $data->eFarm['eFinancialYear']);
				$data->allPeriods = \overview\VatLib::getAllPeriodForFinancialYear($data->eFarm, $data->eFarm['eFinancialYear']);

				$search->set('minDate', $data->vatParameters['from']);
				$search->set('maxDate', $data->vatParameters['to']);

				switch($tab) {

					case NULL:
						break;

					case 'journal-buy':
					case 'journal-sell':
						$type = mb_substr($tab, mb_strlen('journal') + 1);
						$search->buildSort(['date' => SORT_ASC]);
						$data->cOperation = \journal\OperationLib::getAllForVatJournal($type, $search, TRUE, NULL);
						break;

					case 'check':
						$data->check = \overview\VatLib::getForCheck($search);
						break;

					case 'cerfa':
						$data->precision = 0;
						// On tente par l'ID
						$eVatDeclaration = \overview\VatDeclarationLib::getById(GET('id'));
						if($eVatDeclaration->empty()) {
							// On tente par les dates
							$eVatDeclaration = \overview\VatDeclarationLib::getByDates($data->vatParameters['from'], $data->vatParameters['to']);
						}
						// On a trouvé
						if($eVatDeclaration->notEmpty()) {
							$data->cerfa = $eVatDeclaration->getArrayCopy()['data'] + ['eVatDeclaration' => $eVatDeclaration];
						} else {
							// On génère
							$data->cerfa = \overview\VatLib::getVatDataDeclaration($data->eFarm['eFinancialYear'], $search, precision: $data->precision);
						}
						break;

					case 'history':
						$data->cVatDeclaration = \overview\VatDeclarationLib::getHistory($data->eFarm['eFinancialYear']);
						break;
			}
		}

		throw new ViewAction($data, ':'.$data->view);

	})
;
