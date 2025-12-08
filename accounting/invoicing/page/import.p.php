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

	$data->c = match($data->selectedTab) {
		'market' => \invoicing\ImportLib::getMarketSales($data->eFarm, $from, $to),
		'invoice' => \invoicing\ImportLib::getInvoiceSales($data->eFarm, $from, $to),
		'sales' => \invoicing\ImportLib::getSales($data->eFarm, $from, $to),
	};

	throw new ViewAction($data);

});

new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getById(POST('financialYear'));

	})
->post('doImportInvoice', function($data) {

	$eInvoice = \invoicing\ImportLib::getInvoiceById(POST('id', 'int'));

	$eInvoice->validate('acceptAccountingImport');

	$fw = new FailWatch();

	\invoicing\ImportLib::importInvoice($data->eFarm, $eInvoice, $data->eFinancialYear);

	$fw->validate();

	throw new ReloadAction('invoicing', 'Invoice::imported');

})
->post('doImportMarket', function($data) {

	$eSale = \invoicing\ImportLib::getMarketById($data->eFarm, $data->eFinancialYear, POST('id', 'int'))->validate('acceptAccountingImport', 'isMarket');

	$fw = new FailWatch();

	\invoicing\ImportLib::importMarket($data->eFarm, $eSale, $data->eFinancialYear);

	$fw->validate();

	throw new ReloadAction('invoicing', 'Sale::imported.market');

})
->post('doImportSale', function($data) {

	$eSale = \invoicing\ImportLib::getSaleById($data->eFarm, $data->eFinancialYear, POST('id', 'int'))->validate('acceptAccountingImport', 'isSale');

	$fw = new FailWatch();

	\invoicing\ImportLib::importSale($data->eFarm, $eSale, $data->eFinancialYear);

	$fw->validate();

	throw new ReloadAction('invoicing', 'Sale::imported');

})
->post('doIgnoreSale', function($data) {

	$eSale = \selling\SaleLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\invoicing\ImportLib::ignoreSale($eSale);

	throw new ReloadAction('invoicing', $eSale['profile'] === \selling\Sale::MARKET ? 'Sale::ignored.market' : 'Sale::ignored');
})
->post('doIgnoreInvoice', function($data) {

	$eInvoice = \selling\InvoiceLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\invoicing\ImportLib::ignoreInvoice($eInvoice);

	throw new ReloadAction('invoicing', 'Invoice::ignored');
})
->post('doImportSalesCollection', function($data) {

	$cSale = \invoicing\ImportLib::getSalesByIds($data->eFarm, $data->eFinancialYear, POST('ids', 'array'));
	\selling\Sale::validateBatch($cSale);

	\invoicing\ImportLib::importSales($data->eFarm, $cSale, $data->eFinancialYear);

	throw new ReloadAction('invoicing', 'Sale::importedSeveral');

})
->post('doImportMarketCollection', function($data) {

	$cSale = \invoicing\ImportLib::getMarketsByIds($data->eFarm, $data->eFinancialYear, POST('ids', 'array'));
	\selling\Sale::validateBatch($cSale);

	\invoicing\ImportLib::importMarkets($data->eFarm, $cSale, $data->eFinancialYear);

	throw new ReloadAction('invoicing', 'Sale::importedSeveral');

})
->post('doImportInvoiceCollection', function($data) {

	$cInvoice = \invoicing\ImportLib::getInvoicesByIds(POST('ids', 'array'));
	\selling\Invoice::validateBatch($cInvoice);

	\invoicing\ImportLib::importInvoices($data->eFarm, $cInvoice, $data->eFinancialYear);

	throw new ReloadAction('invoicing', 'Invoice::importedSeveral');

})
->post('doIgnoreCollection', function($data) {

	switch(POST('type')) {

		case 'invoice':
			$cInvoice = \selling\InvoiceLib::getByIds(POST('ids', 'array'))->validate('acceptAccountingIgnore');
			\selling\Invoice::validateBatch($cInvoice);
			\invoicing\ImportLib::ignoreInvoices($cInvoice);
			break;

		case 'sales':
		case 'market':
			$cSale = \selling\SaleLib::getByIds(POST('ids', 'array'))->validate('acceptAccountingIgnore');
			\selling\Sale::validateBatch($cSale);
			\invoicing\ImportLib::ignoreSales($cSale);
			break;

		default:
			throw new \FailAction('Accounting\Invoicing::salesOrInvoices.check');

	}

	throw new ReloadAction('invoicing', mb_ucfirst(POST('type')).'::ignoredSeveral');
})
;

?>
