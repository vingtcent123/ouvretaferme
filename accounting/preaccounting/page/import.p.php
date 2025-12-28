<?php
new Page()
->post('doImportInvoice', function($data) {

	$eInvoice = \preaccounting\ImportLib::getInvoiceById(POST('id', 'int'));

	$eInvoice->validate('acceptAccountingImport');

	$fw = new FailWatch();

	\preaccounting\ImportLib::importInvoice($data->eFarm, $eInvoice);

	$fw->validate();

	\account\LogLib::save('import', 'Invoice', ['id' => $eInvoice['id']]);

	throw new ReloadAction('preaccounting', 'Invoice::imported');

})
->post('doIgnoreInvoice', function($data) {

	$eInvoice = \selling\InvoiceLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\preaccounting\ImportLib::ignoreInvoice($eInvoice);

	\account\LogLib::save('ignore', 'Invoice', ['id' => $eInvoice['id']]);

	throw new ReloadAction('preaccounting', 'Invoice::ignored');
})
->post('doImportInvoiceCollection', function($data) {

	$cInvoice = \preaccounting\ImportLib::getInvoicesByIds(POST('ids', 'array'));
	\selling\Invoice::validateBatch($cInvoice);

	\preaccounting\ImportLib::importInvoices($data->eFarm, $cInvoice);

	\account\LogLib::save('importSeveral', 'Invoice', ['ids' => $cInvoice->getIds()]);

	throw new ReloadAction('preaccounting', 'Invoice::importedSeveral');

})
->post('doIgnoreCollection', function($data) {

	$cInvoice = \selling\InvoiceLib::getByIds(POST('ids', 'array'))->validate('acceptAccountingIgnore');

	\selling\Invoice::validateBatch($cInvoice);
	\preaccounting\ImportLib::ignoreInvoices($cInvoice);

	\account\LogLib::save('ignoreSeveral', 'Invoice', ['ids' => $cInvoice->getIds()]);

	throw new ReloadAction('preaccounting', 'Invoice::ignoredSeveral');

})
;

?>
