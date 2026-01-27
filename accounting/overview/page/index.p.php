<?php
new Page(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->get(['/etats-financiers/', '/etats-financiers/{view}'], function($data) {

		$fqn = array_column(\farm\FarmUi::getAccountingFinancialsCategories($data->eFarm['eFinancialYear']), 'fqn');
		if(in_array(GET('view'), $fqn) === FALSE) {
			$data->fqn = first($fqn);
		} else {
			$data->fqn = GET('view', 'string', '');
		}

		$data->view = array_find_key(\farm\FarmUi::getAccountingFinancialsCategories($data->eFarm['eFinancialYear']), fn($element) => (is_null($element) === FALSE and $element['fqn'] === $data->fqn));

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

			case \overview\AnalyzeLib::TAB_FINANCIAL_YEAR:
				$data->eFinancialYear = $data->eFarm['eFinancialYear'];

				$data->eFinancialYear['nOperation'] = \journal\OperationLib::countByFinancialYear($data->eFinancialYear);
				$data->eFinancialYear['previous'] = \account\FinancialYearLib::getPreviousFinancialYear($data->eFinancialYear);

				$data->eFinancialYear['cImport'] = \account\ImportLib::getByFinancialYear($data->eFinancialYear);
				break;


			case \overview\AnalyzeLib::TAB_BANK:
				$data->ccOperationBank = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFarm['eFinancialYear'], 'bank');
				$data->ccOperationCash = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFarm['eFinancialYear'], 'cash');
				break;

			case \overview\AnalyzeLib::TAB_CHARGES:
				[$data->cOperation, $data->cAccount] = \overview\AnalyzeLib::getChargeOperationsByMonth($data->eFarm['eFinancialYear']);
				$data->cOperationResult = \overview\AnalyzeLib::getResultOperationsByMonth($data->eFarm['eFinancialYear']);
				break;

			case \overview\AnalyzeLib::TAB_SIG:

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
				$data->eFinancialYearDocument = \account\FinancialYearDocumentLib::getDocument($data->eFarm['eFinancialYear'], \account\FinancialYearDocumentLib::SIG);
				break;

			case \overview\AnalyzeLib::TAB_BALANCE_SHEET:

				$data->search->set('type', GET('type', 'string', \overview\BalanceSheetLib::VIEW_BASIC));

				$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->eFarm['eFinancialYear']);

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
							[\account\AccountSetting::LOSS_RESULT_CLASS, \account\AccountSetting::PROFIT_RESULT_CLASS])
					);

					$data->cAccount = \account\AccountLib::getByClasses($classes, 'class');

				}

				$data->eFinancialYearDocument = \account\FinancialYearDocumentLib::getDocument(
					$data->eFarm['eFinancialYear'],
					$data->eFarm['eFinancialYear']->isClosed() ? \account\FinancialYearDocumentLib::CLOSING : \account\FinancialYearDocumentLib::BALANCE_SHEET
				);

				break;

			case \overview\AnalyzeLib::TAB_INCOME_STATEMENT:

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

				$data->eFinancialYearDocument = \account\FinancialYearDocumentLib::getDocument(
					$data->eFarm['eFinancialYear'],
					GET('type') === 'detailed' ? \account\FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED : \account\FinancialYearDocumentLib::INCOME_STATEMENT
				);

				break;

			case \overview\AnalyzeLib::TAB_VAT:

				$tab = GET('tab');
				if(in_array($tab, ['journal-sell', 'journal-buy', 'check', 'cerfa', 'history']) === FALSE) {
					$tab = NULL;
				}
				$data->tab = $tab;

				$search = new Search();
				// On va filtrer sur les dates de la période de TVA
				$search->set('financialYear', new \account\FinancialYear());

				$data->allPeriods = \overview\VatLib::getAllPeriodForFinancialYear($data->eFarm, $data->eFarm['eFinancialYear']);
				$data->vatParameters = \overview\VatLib::extractCurrentPeriod($data->allPeriods);

				$search->set('minDate', $data->vatParameters['from']);
				$search->set('maxDate', $data->vatParameters['to']);

				switch($tab) {

					case NULL:
						break;

					case 'journal-buy':
					case 'journal-sell':
						$type = mb_substr($tab, mb_strlen('journal') + 1);
						$search->validateSort(['date']);
						$data->cOperation = \journal\OperationLib::getAllForVatJournal($type, $search, TRUE, NULL);
						break;

					case 'check':
						$data->check = \overview\VatLib::getForCheck($data->eFarm, $search);
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
							$data->cerfa = \overview\VatLib::getVatDataDeclaration($data->eFarm, $data->eFarm['eFinancialYear'], $search, precision: $data->precision);
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
