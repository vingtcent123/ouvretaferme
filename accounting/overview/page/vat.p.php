<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

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
	->get('/etats-financiers/declaration-de-tva/operations', function($data) {

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
