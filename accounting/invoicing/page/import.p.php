<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

})
->get('/ventes/importer', function($data) {

	$data->selectedTab = in_array(GET('tab'), ['market', 'invoice', 'sales']) ? GET('tab') : 'market';

	$from = $data->eFinancialYear['startDate'];
	$to = $data->eFinancialYear['endDate'];

	$data->cSale = match($data->selectedTab) {
		'market' => \invoicing\ImportLib::getMarketSales($data->eFarm, $from, $to),
	};

	throw new ViewAction($data);

})
->post('doImport', function($data) {
	// check adéquation efinancialYear + eSale
	// créer tiers si non existant (+ rattache à customer)
	// créer un hash avec dernier caractère = v (ventes)
	// preparer les opérations
	// enregistrer les opérations
	dd($_POST);

})
->post('doIgnore', function($data) {

	$eSale = \selling\SaleLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\invoicing\ImportLib::ignoreSale($eSale);

	throw new ReloadAction('invoicing', $eSale['profile'] === \selling\Sale::MARKET ? 'Sale::ignored.market' : 'Sale::ignored');
});

?>
