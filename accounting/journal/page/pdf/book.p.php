<?php
new Page()
	->remote('index', 'accounting', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'));

		$data->eFinancialYearSelected = \account\FinancialYearLib::getById(GET('financialYear'))->validate();

		$search = new Search(['financialYear' => $data->eFinancialYearSelected]);

		$data->cOperation = \journal\OperationLib::getAllForBook($search);

		throw new ViewAction($data);

	});
?>
