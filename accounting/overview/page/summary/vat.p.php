<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->get('index', function($data) {

		if($data->eFinancialYear['status'] === \account\FinancialYear::CLOSE) {
			// TODO DEV throw new RedirectAction(\company\CompanyUi::urlSummary($data->eFarm).'/vathistory');
		}

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

		$data->vatParameters = \overview\VatLib::getVatDeclarationParameters($data->eFarm, $data->eFinancialYear);
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

		throw new ViewAction($data);

	})
	->post('saveCerfa', function($data) {

		$from = POST('from');
		$to = POST('to');
		$data->vatParameters = \overview\VatLib::getVatDeclarationParameters($data->eFarm, $data->eFinancialYear);

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
	->post('reset', function($data) {

		$eVatDeclaration = \overview\VatDeclarationLib::getByDates(POST('from'), POST('to'))->validate('canUpdate');

		\overview\VatDeclarationLib::delete($eVatDeclaration);

		throw new ReloadAction('overview', 'VatDeclaration::reset');

	})
	->post('doDeclare', function($data) {

		$eVatDeclaration = \overview\VatDeclarationLib::getById(POST('id'))->validate('canUpdate');

		\overview\VatDeclarationLib::declare($eVatDeclaration);

		throw new ReloadAction('overview', 'VatDeclaration::declared');

	})
	->get('operations', function($data) {

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
	->post('doCreateOperations', function($data) {

		$eVatDeclaration = \overview\VatDeclarationLib::getById(POST('id'));

		if($eVatDeclaration['status'] !== \overview\VatDeclaration::DECLARED) {
			throw new NotExpectedAction('Unable to create operations from non-declared vat declaration');
		}

		\overview\VatLib::createOperations($data->eFarm, $eVatDeclaration, $data->eFinancialYear);

		throw new ReloadAction('overview', 'VatDeclaration::operationsCreated');

	});
?>
