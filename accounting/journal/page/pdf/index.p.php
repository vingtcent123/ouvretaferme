<?php
new Page()
	->get('index', function($data) {

		$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canRemote');

		$data->eFinancialYear = \accounting\FinancialYearLib::getById(GET('financialYear'));
		if($data->eFinancialYear->exists() === FALSE) {
			throw new NotExpectedAction('Cannot generate PDF of book with no financial year');
		}

		$search = new Search(['financialYear' => $data->eFinancialYear]);

		$data->cOperation = \journal\OperationLib::getAllForJournal($search);

		throw new ViewAction($data);

	});
?>
