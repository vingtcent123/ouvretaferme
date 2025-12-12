<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

})
->get('/facturation-electronique', function($data) {

	throw new ViewAction($data);

});

new Page(

	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

})
->get('/factures/', function($data) {

	$data->selectedTab = in_array(GET('tab'), ['buy', 'sell']) ? GET('buy') : 'sell';

	$from = $data->eFinancialYear['startDate'];
	$to = $data->eFinancialYear['endDate'];
	$search = new Search();

	$data->counts = \invoicing\InvoiceLib::counts($data->eFarm, $from, $to, $search);

	throw new ViewAction($data);

});


?>
