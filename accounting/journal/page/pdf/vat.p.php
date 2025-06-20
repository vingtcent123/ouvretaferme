<?php
new Page()
	->get('index', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canRemote');

		$data->eFinancialYear = \account\FinancialYearLib::getById(GET('financialYear'));
		if($data->eFinancialYear->exists() === FALSE) {
			throw new NotExpectedAction('Cannot generate PDF of vat journal with no financial year');
		}

		$data->type = GET('type',  'string', 'buy');
		if(in_array($data->type, ['buy', 'sell']) === FALSE) {
			throw new NotExpectedAction('Cannot generate PDF of vat journal with no type');
		}


		$search = new Search(['financialYear' => $data->eFinancialYear]);

		$data->cccOperation = journal\OperationLib::getAllForVatJournal($data->type, $search);

		throw new ViewAction($data);

	});
?>
