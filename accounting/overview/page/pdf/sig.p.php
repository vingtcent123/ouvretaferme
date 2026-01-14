<?php
new Page()
	->remote('index', 'accounting', function($data) {

		$data->eFinancialYear = $data->eFarm['eFinancialYear'];
		$data->eFinancialYearComparison = \account\FinancialYearLib::getPreviousFinancialYear($data->eFinancialYear);

		$values = \overview\SigLib::compute($data->eFinancialYear);
		$data->values = [
			$data->eFinancialYear['id'] => $values
		];

		if($data->eFinancialYearComparison->notEmpty()) {
			$data->values[$data->eFinancialYearComparison['id']] = \overview\SigLib::compute($data->eFinancialYearComparison);
		}

		throw new ViewAction($data);

	});
?>
