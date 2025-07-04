<?php
new Page(function($data) {

	$data->eFarm['company']->validate('canRemote');

	$data->eFinancialYear = \account\FinancialYearLib::getById(GET('financialYear'));
	if($data->eFinancialYear->exists() === FALSE) {
		throw new NotExpectedAction('Cannot generate PDF of book with no financial year');
	}

})
	->get('summary', function($data) {

		$data->balanceSummarized = \overview\BalanceLib::getSummarizedBalance($data->eFinancialYear);

		\account\LogLib::save('getSummary', 'Overview', ['financialYear' => $data->eFinancialYear['id']]);

		throw new ViewAction($data);
	})
	->get('opening', function($data) {

		$data->balanceSummarized = \overview\BalanceLib::getOpeningBalance($data->eFinancialYear);

		\account\LogLib::save('getOpening', 'Overview', ['financialYear' => $data->eFinancialYear['id']]);

		throw new ViewAction($data, ':summary');
	});
?>
