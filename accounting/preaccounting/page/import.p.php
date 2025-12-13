<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getById(POST('financialYear'));

	})
->post('doImportInvoice', function($data) {

	$eInvoice = \preaccounting\ImportLib::getInvoiceById(POST('id', 'int'));

	$eInvoice->validate('acceptAccountingImport');

	$fw = new FailWatch();

	\preaccounting\ImportLib::importInvoice($data->eFarm, $eInvoice, $data->eFinancialYear);

	$fw->validate();

	\account\LogLib::save('import', 'Invoice', ['id' => $eInvoice['id']]);

	throw new ReloadAction('preaccounting', 'Invoice::imported');

})
->post('doImportMarket', function($data) {

	$eSale = \preaccounting\ImportLib::getMarketById($data->eFarm, $data->eFinancialYear, POST('id', 'int'))->validate('acceptAccountingImport', 'isMarket');

	$fw = new FailWatch();

	\preaccounting\ImportLib::importMarket($data->eFarm, $eSale, $data->eFinancialYear);

	$fw->validate();

	\account\LogLib::save('import', 'Market', ['id' => $eSale['id']]);

	throw new ReloadAction('preaccounting', 'Sale::imported.market');

})
->post('doImportSale', function($data) {

	$eSale = \preaccounting\ImportLib::getSaleById($data->eFarm, $data->eFinancialYear, POST('id', 'int'))->validate('acceptAccountingImport', 'isSale');

	$fw = new FailWatch();

	\preaccounting\ImportLib::importSale($data->eFarm, $eSale, $data->eFinancialYear);

	$fw->validate();

	\account\LogLib::save('import', 'Sales', ['id' => $eSale['id']]);

	throw new ReloadAction('preaccounting', 'Sale::imported');

})
->post('doIgnoreSale', function($data) {

	$eSale = \selling\SaleLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\preaccounting\ImportLib::ignoreSale($eSale);

	\account\LogLib::save('ignore', 'Sales', ['id' => $eSale['id']]);

	throw new ReloadAction('preaccounting', $eSale['profile'] === \selling\Sale::MARKET ? 'Sale::ignored.market' : 'Sale::ignored');
})
->post('doIgnoreInvoice', function($data) {

	$eInvoice = \selling\InvoiceLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\preaccounting\ImportLib::ignoreInvoice($eInvoice);

	\account\LogLib::save('ignore', 'Invoice', ['id' => $eInvoice['id']]);

	throw new ReloadAction('preaccounting', 'Invoice::ignored');
})
->post('doImportSalesCollection', function($data) {

	$cSale = \preaccounting\ImportLib::getSalesByIds($data->eFarm, $data->eFinancialYear, POST('ids', 'array'));
	\selling\Sale::validateBatch($cSale);

	\preaccounting\ImportLib::importSales($data->eFarm, $cSale, $data->eFinancialYear);

	\account\LogLib::save('importSeveral', 'Sales', ['ids' => $cSale->getIds()]);

	throw new ReloadAction('preaccounting', 'Sale::importedSeveral');

})
->post('doImportMarketCollection', function($data) {

	$cSale = \preaccounting\ImportLib::getMarketsByIds($data->eFarm, $data->eFinancialYear, POST('ids', 'array'));
	\selling\Sale::validateBatch($cSale);

	\preaccounting\ImportLib::importMarkets($data->eFarm, $cSale, $data->eFinancialYear);

	\account\LogLib::save('importSeveral', 'Market', ['ids' => $cSale->getIds()]);

	throw new ReloadAction('preaccounting', 'Sale::importedSeveral');

})
->post('doImportInvoiceCollection', function($data) {

	$cInvoice = \preaccounting\ImportLib::getInvoicesByIds(POST('ids', 'array'));
	\selling\Invoice::validateBatch($cInvoice);

	\preaccounting\ImportLib::importInvoices($data->eFarm, $cInvoice, $data->eFinancialYear);

	\account\LogLib::save('importSeveral', 'Invoice', ['ids' => $cInvoice->getIds()]);

	throw new ReloadAction('preaccounting', 'Invoice::importedSeveral');

})
->post('doIgnoreCollection', function($data) {

	switch(POST('type')) {

		case 'invoice':
			$cInvoice = \selling\InvoiceLib::getByIds(POST('ids', 'array'))->validate('acceptAccountingIgnore');
			\selling\Invoice::validateBatch($cInvoice);
			\preaccounting\ImportLib::ignoreInvoices($cInvoice);
			\account\LogLib::save('ignoreSeveral', 'Invoice', ['ids' => $cInvoice->getIds()]);
			break;

		case 'sales':
		case 'market':
			$cSale = \selling\SaleLib::getByIds(POST('ids', 'array'))->validate('acceptAccountingIgnore');
			\selling\Sale::validateBatch($cSale);
			\preaccounting\ImportLib::ignoreSales($cSale);
			\account\LogLib::save('ignoreSeveral', POST('type'), ['ids' => $cSale->getIds()]);
			break;

		default:
			throw new \FailAction('Accounting\Invoicing::salesOrInvoices.check');

	}

	throw new ReloadAction('preaccounting', mb_ucfirst(POST('type')).'::ignoredSeveral');
})
;

?>
