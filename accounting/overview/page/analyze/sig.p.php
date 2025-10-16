<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$data->search = new Search([
		'financialYearComparison' => GET('financialYearComparison'),
	], GET('sort'));

})
	->get(['index'], function($data) {

		$values = \overview\SigLib::compute($data->eFinancialYear);
		$data->values = [
			$data->eFinancialYear['id'] => $values
		];

		if($data->search->get('financialYearComparison') and (int)$data->search->get('financialYearComparison') !== $data->eFinancialYear['id']) {
			$data->eFinancialYearComparison = \account\FinancialYearLib::getById($data->search->get('financialYearComparison'));
			if($data->eFinancialYearComparison->notEmpty()) {
				$data->values[$data->eFinancialYearComparison['id']] = \overview\SigLib::compute($data->eFinancialYearComparison);
			}
		} else {
			$data->eFinancialYearComparison = new \account\FinancialYear();
		}

		throw new ViewAction($data);

	});
?>
