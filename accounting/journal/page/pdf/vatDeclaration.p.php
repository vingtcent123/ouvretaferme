<?php
new Page()
	->get('index', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canRemote');

		$data->eVatDeclaration = \journal\VatDeclarationLib::getById(GET('id'));

		if($data->eVatDeclaration->empty()) {
			throw new NotExpectedAction('Cannot generate PDF of vat statement with unknown id');
		}

		$data->eFinancialYear = $data->eVatDeclaration['financialYear'];
		$data->eFinancialYear['lastPeriod'] = \journal\VatDeclarationLib::calculateLastPeriod($data->eFinancialYear);

		$search = new Search(['financialYear' => $data->eFinancialYear, 'vatDeclaration' => $data->eVatDeclaration]);

		$data->cOperation = journal\OperationLib::getAllForVatDeclaration($search);

		throw new ViewAction($data);

	});
