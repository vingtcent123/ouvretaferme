<?php
new Page(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

	if($data->eFarm['eFinancialYear']['hasVatAccounting'] === FALSE) {
		throw new NotExistsAction();
	}

})
	->get('/declaration-de-tva', function($data) {

		if($data->eFarm['eFinancialYear']['hasVatAccounting'] === FALSE) {
			throw new NotExistsAction();
		}

		$tab = GET('tab');
		if(in_array($tab, ['journal-sell', 'journal-buy', 'check', 'cerfa', 'history']) === FALSE) {
			$tab = NULL;
		}
		$data->tab = $tab;

		$from = GET('from');

		$search = new Search();

		// On va filtrer sur les dates de la période de TVA et pas sur le financialYear
		$search->set('financialYear', new \account\FinancialYear());

		$data->allPeriods = \vat\VatLib::getAllPeriodForFinancialYear($data->eFarm, $data->eFarm['eFinancialYear']);
		$data->vatParameters = \vat\VatLib::extractCurrentPeriod($data->allPeriods, $from);

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
				$data->check = \vat\VatLib::getForCheck($data->eFarm, $search);
				break;

			case 'cerfa':
				$data->check = \vat\VatLib::getForCheck($data->eFarm, $search);
				$data->precision = 0;

				// On tente par l'ID
				$eDeclaration = \vat\DeclarationLib::getById(GET('id'));
				if($eDeclaration->hasData() === FALSE) {
					// On tente par les dates
					$eDeclaration = \vat\DeclarationLib::getByDates($data->vatParameters['from'], $data->vatParameters['to']);
				}
				// On a trouvé
				if($eDeclaration->hasData()) {

					$data->cerfa = $eDeclaration->getArrayCopy()['data'] + ['eDeclaration' => $eDeclaration];
					$data->vatParameters = [
						'limit' => $eDeclaration['limit'],
						'from' => $eDeclaration['from'],
						'to' => $eDeclaration['to'],
					];

				} else {

					// On génère
					$search = new Search([
						'minDate' => $data->vatParameters['from'],
						'maxDate' => $data->vatParameters['to'],
						'financialYear' => new \account\FinancialYear(),
					]);
					$data->cerfa = \vat\VatLib::getVatDataDeclaration($data->eFarm, $search, precision: $data->precision);

				}

				$data->adarBase = \vat\AdarLib::getTaxableBase($data->eFarm['eFinancialYear'], $data->vatParameters);
				break;

			case 'history':
				$data->cDeclaration = \vat\DeclarationLib::getHistory($data->eFarm['eFinancialYear']);
				break;
		}

		throw new ViewAction($data);

	})
	->post('/vat/saveCerfa', function($data) {

		$from = POST('from');
		$to = POST('to');
		$data->vatParameters = \vat\VatLib::getPeriodForDates($data->eFarm, $data->eFarm['eFinancialYear'], $from, $to);

		if($data->vatParameters === NULL) {
			throw new NotExpectedAction('Unable to update for this VAT declaration dates');
		}

		$input = $_POST;
		unset($input['from']);
		unset($input['to']);
		unset($input['financialYear']);

		\vat\DeclarationLib::saveCerfa($data->eFarm, $data->eFarm['eFinancialYear'], $from, $to, $input, $data->vatParameters['limit']);

		throw new ReloadAction('vat', 'Declaration::saved');

	})
	->get('/etats-financiers/declaration-de-tva/operations', function($data) {

		$data->eDeclaration = \vat\DeclarationLib::getById(GET('id'));

		if($data->eDeclaration->empty()) {
			throw new NotExistsAction('Unknown declaration');
		}

		$data->eFinancialYearOperations = \account\FinancialYearLib::getNextOpenFinancialYearByDate(date('Y-m-d', strtotime($data->eDeclaration['to'].' + 1 DAY')));

		$dataFromDeclaration = \vat\VatLib::generateOperationsFromDeclaration($data->eFarm, $data->eDeclaration);
		$data->cerfaCalculated = $dataFromDeclaration['cerfaCalculated'];
		$data->cerfaDeclared = $dataFromDeclaration['cerfaDeclared'];
		$data->cOperation = $dataFromDeclaration['cOperation'];

		throw new ViewAction($data);

	})
;

new \vat\DeclarationPage(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

	if($data->eFarm['eFinancialYear']['hasVatAccounting'] === FALSE) {
		throw new NotExistsAction();
	}

})
	->write('/vat/doDeclare', function($data) {

		\vat\DeclarationLib::declare($data->e);

		throw new ReloadAction('vat', 'Declaration::declared');

	}, validate: ['acceptDeclare'])
	->write('/vat/doReset', function($data) {

		\vat\DeclarationLib::delete($data->e);

		throw new ReloadAction('vat', 'Declaration::reset');

	})
	->write('/vat/doCreateOperations', function($data) {

		$eFinancialYear = \account\FinancialYearLib::getNextOpenFinancialYearByDate(date('Y-m-d', strtotime($data->e['to'].' + 1 DAY')));
		if($eFinancialYear->empty()) {
			throw new FailAction('vat\Vat::createOperations.noFinancialYear');
		}

		\vat\VatLib::createOperations($data->eFarm, $data->e, $eFinancialYear);

		throw new ReloadAction('vat', 'Declaration::operationsCreated');

	}, validate: ['acceptAccount'])
?>
