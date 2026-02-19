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

		$data->allPeriods = \overview\VatLib::getAllPeriodForFinancialYear($data->eFarm, $data->eFarm['eFinancialYear']);
		$data->vatParameters = \overview\VatLib::extractCurrentPeriod($data->allPeriods, $from);

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
				$data->check = \overview\VatLib::getForCheck($data->eFarm, $search);
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
					$data->vatParameters = [
						'limit' => $eVatDeclaration['limit'],
						'from' => $eVatDeclaration['from'],
						'to' => $eVatDeclaration['to'],
					];
				} else {

					// On génère
					$search = new Search([
						'minDate' => $data->vatParameters['from'],
						'maxDate' => $data->vatParameters['to'],
						'financialYear' => new \account\FinancialYear(),
					]);
					$data->cerfa = \overview\VatLib::getVatDataDeclaration($data->eFarm, $data->eFarm['eFinancialYear'], $search, precision: $data->precision);

				}
				break;

			case 'history':
				$data->cVatDeclaration = \overview\VatDeclarationLib::getHistory($data->eFarm['eFinancialYear']);
				break;
		}

		throw new ViewAction($data);

	})
	->post('/vat/saveCerfa', function($data) {

		$from = POST('from');
		$to = POST('to');
		$data->vatParameters = \overview\VatLib::getPeriodForDates($data->eFarm, $data->eFarm['eFinancialYear'], $from, $to);

		if($data->vatParameters === NULL) {
			throw new NotExpectedAction('Unable to update for this VAT declaration dates');
		}

		$input = $_POST;
		unset($input['from']);
		unset($input['to']);
		unset($input['financialYear']);

		\overview\VatDeclarationLib::saveCerfa($data->eFarm, $data->eFarm['eFinancialYear'], $from, $to, $input, $data->vatParameters['limit']);

		throw new ReloadAction('overview', 'VatDeclaration::saved');

	})
	->get('/etats-financiers/declaration-de-tva/operations', function($data) {

		$data->eVatDeclaration = \overview\VatDeclarationLib::getById(GET('id'));

		if($data->eVatDeclaration->empty()) {
			throw new NotExistsAction('Unknown declaration');
		}

		$dataFromDeclaration = \overview\VatLib::generateOperationsFromDeclaration($data->eFarm, $data->eVatDeclaration, $data->eFarm['eFinancialYear']);
		$data->cerfaCalculated = $dataFromDeclaration['cerfaCalculated'];
		$data->cerfaDeclared = $dataFromDeclaration['cerfaDeclared'];
		$data->cOperation = $dataFromDeclaration['cOperation'];

		throw new ViewAction($data);

	})
;

new \overview\VatDeclarationPage(function($data) {

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

		\overview\VatDeclarationLib::declare($data->e);

		throw new ReloadAction('overview', 'VatDeclaration::declared');

	}, validate: ['acceptDeclare'])
	->write('/vat/doReset', function($data) {

		\overview\VatDeclarationLib::delete($data->e);

		throw new ReloadAction('overview', 'VatDeclaration::reset');

	})
	->write('/vat/doCreateOperations', function($data) {

		// TODO : savoir dans quel financial year écrire
		\overview\VatLib::createOperations($data->eFarm, $data->e, $data->eFarm['eFinancialYear']);

		throw new ReloadAction('overview', 'VatDeclaration::operationsCreated');

	}, validate: ['acceptAccount'])
?>
