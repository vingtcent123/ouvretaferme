<?php
new Page()
	->remote('index', 'accounting', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'));

		$data->eFinancialYear = \account\FinancialYearLib::getById(GET('financialYear'));
		if($data->eFinancialYear->exists() === FALSE) {
			throw new NotExpectedAction('Cannot generate PDF of book with no financial year');
		}

		$search = new Search(['financialYear' => $data->eFinancialYear]);

		$data->cOperation = \journal\OperationLib::getAllForBook($search);

		throw new ViewAction($data);

	});
?>
