<?php

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
	->write('doReset', function($data) {

		\vat\DeclarationLib::delete($data->e);

		throw new ReloadAction('vat', 'Declaration::reset');

	}, validate: ['acceptReset'])
	->write('doCreateOperations', function($data) {

		$eFinancialYear = \account\FinancialYearLib::getNextOpenFinancialYearByDate(date('Y-m-d', strtotime($data->e['to'].' + 1 DAY')));
		if($eFinancialYear->empty()) {
			throw new FailAction('vat\Vat::createOperations.noFinancialYear');
		}

		\vat\VatLib::createOperations($data->eFarm, $data->e, $eFinancialYear);

		throw new ReloadAction('vat', 'Declaration::operationsCreated');

	}, validate: ['acceptAccount'])
	->write('doUpdateDeclareStatus', function($data) {

		$data->e['status'] = \vat\Declaration::DECLARED;
		\vat\DeclarationLib::update($data->e, ['status']);

		throw new ReloadAction('vat', 'Declaration::status.declared');
	}, validate: ['canWrite', 'acceptDeclare'])
	->write('doUpdateAccountStatus', function($data) {

		$data->e['status'] = \vat\Declaration::ACCOUNTED;
		\vat\DeclarationLib::update($data->e, ['status']);

		throw new ReloadAction('vat', 'Declaration::status.accounted');
	}, validate: ['canWrite', 'acceptAccount'])
	->write('doUpdatePaymentStatus', function($data) {

		$data->e['status'] = \vat\Declaration::PAID;
		\vat\DeclarationLib::update($data->e, ['status']);

		throw new ReloadAction('vat', 'Declaration::status.paid');
	}, validate: ['canWrite', 'acceptPay'])
;
