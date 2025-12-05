<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$views = array_column(\farm\FarmUi::getAccountingSummaryCategories(), 'fqn');

	if(get_exists('view') and in_array(GET('view'), $views)) {

		$view = array_find_key(\farm\FarmUi::getAccountingSummaryCategories(), fn($category) => $category['fqn'] === GET('view'));
		$data->view = \farm\FarmerLib::setView('viewAccountingSummary', $data->eFarm, $view);

	} else {

		$data->view = $data->eFarm->getView('viewAccountingSummary');

	}

})
	->get(['/synthese', '/synthese/{view}'], function($data) {

		$data->search = new Search([
			'financialYearComparison' => GET('financialYearComparison'),
		], GET('sort'));

		if($data->search->get('financialYearComparison')) {
			$data->eFinancialYearComparison = \account\FinancialYearLib::getById($data->search->get('financialYearComparison'));
		} else {
			$data->eFinancialYearComparison = new \account\FinancialYear();
		}

		switch($data->view) {

			case \farm\Farmer::BALANCE_SHEET:

				$data->search->set('type', GET('type', 'string', \overview\BalanceSheetLib::VIEW_BASIC));

				[$data->balanceSheetData, $data->totals] = \overview\BalanceSheetLib::getData(
					eFinancialYear: $data->eFinancialYear,
					eFinancialYearComparison: $data->eFinancialYearComparison,
					isDetailed: $data->search->get('type') === \overview\BalanceSheetLib::VIEW_DETAILED
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
				break;

			case \farm\Farmer::INCOME_STATEMENT:

				$data->search->set('type', GET('type', 'string', \overview\IncomeStatementLib::VIEW_BASIC));

				$data->resultData = \overview\IncomeStatementLib::getResultOperationsByFinancialYear(
					eFinancialYear: $data->eFinancialYear,
					isDetailed: $data->search->get('type') === \overview\IncomeStatementLib::VIEW_DETAILED,
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

				break;

			case \farm\Farmer::VAT:

				if($data->eFinancialYear['hasVat'] === FALSE) {
					throw new ViewAction($data, ':noVat');
				}

				$tab = GET('tab');
				if(in_array($tab, ['journal-sell', 'journal-buy', 'check', 'cerfa', 'history']) === FALSE) {
					$tab = NULL;
				}
				$data->tab = $tab;

				$search = new Search();
				$search->set('financialYear', $data->eFinancialYear);

				$data->vatParameters = \overview\VatLib::getDefaultPeriod($data->eFarm, $data->eFinancialYear);
				$data->allPeriods = \overview\VatLib::getAllPeriodForFinancialYear($data->eFarm, $data->eFinancialYear);

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
							$data->cerfa = \overview\VatLib::getVatDataDeclaration($data->eFinancialYear, $search, precision: $data->precision);
						}
						break;

					case 'history':
						$data->cVatDeclaration = \overview\VatDeclarationLib::getHistory($data->eFinancialYear);
						break;
				}

		}

		throw new ViewAction($data, ':'.$data->view);

	});


new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->post('/vat/saveCerfa', function($data) {

		$from = POST('from');
		$to = POST('to');
		$data->vatParameters = \overview\VatLib::getDefaultPeriod($data->eFarm, $data->eFinancialYear);

		if($data->vatParameters['from'] !== $from and $data->vatParameters['to'] !== $to) {
			throw new NotExpectedAction('Unable to update for this VAT declaration dates');
		}

		$input = $_POST;
		unset($input['from']);
		unset($input['to']);
		unset($input['financialYear']);

		\overview\VatDeclarationLib::saveCerfa($data->eFinancialYear, $from, $to, $input, $data->vatParameters['limit']);

		throw new ReloadAction('overview', 'VatDeclaration::saved');

	})
	->post('/vat/reset', function($data) {

		$eVatDeclaration = \overview\VatDeclarationLib::getByDates(POST('from'), POST('to'))->validate('canUpdate');

		\overview\VatDeclarationLib::delete($eVatDeclaration);

		throw new ReloadAction('overview', 'VatDeclaration::reset');

	})
	->post('/vat/doDeclare', function($data) {

		$eVatDeclaration = \overview\VatDeclarationLib::getById(POST('id'))->validate('canUpdate');

		\overview\VatDeclarationLib::declare($eVatDeclaration);

		throw new ReloadAction('overview', 'VatDeclaration::declared');

	})
	->get('/synthese/declaration-de-tva/operations', function($data) {

		$data->eVatDeclaration = \overview\VatDeclarationLib::getById(GET('id'));

		if($data->eVatDeclaration->empty()) {
			throw new NotExistsAction('Unknown declaration');
		}

		$dataFromDeclaration = \overview\VatLib::generateOperationsFromDeclaration($data->eVatDeclaration, $data->eFinancialYear);
		$data->cerfaCalculated = $dataFromDeclaration['cerfaCalculated'];
		$data->cerfaDeclared = $dataFromDeclaration['cerfaDeclared'];
		$data->cOperation = $dataFromDeclaration['cOperation'];

		throw new ViewAction($data);

	})
	->post('/vat/doCreateOperations', function($data) {

		$eVatDeclaration = \overview\VatDeclarationLib::getById(POST('id'));

		if($eVatDeclaration['status'] !== \overview\VatDeclaration::DECLARED) {
			throw new NotExpectedAction('Unable to create operations from non-declared vat declaration');
		}

		\overview\VatLib::createOperations($data->eFarm, $eVatDeclaration, $data->eFinancialYear);

		throw new ReloadAction('overview', 'VatDeclaration::operationsCreated');

	});
?>
