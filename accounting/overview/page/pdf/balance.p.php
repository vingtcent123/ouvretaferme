<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getById(GET('financialYear'));
	if($data->eFinancialYear->exists() === FALSE) {
		throw new NotExpectedAction('Cannot generate PDF of book with no financial year');
	}

})
	->get('summary', function($data) {

		$data->balanceSummarized = \overview\BalanceLib::getSummarizedBalance($data->eFinancialYear);

		throw new ViewAction($data);
	})
	->get('opening', function($data) {

		$data->balanceSummarized = \overview\BalanceLib::getOpeningBalance($data->eFinancialYear);

		throw new ViewAction($data, ':summary');
	});
?>
