<?php
new \selling\InvoicePage()
	->write('updateInvoiceAccountingDifference', function($data) {

		if($data->e['cashflow']->empty()) {
			throw new NotExpectedAction('Invoice does not accept accountingDifference update.');
		}

		$data->e['cashflow'] = \bank\CashflowLib::getById($data->e['cashflow']['id']);
		$data->e->validate('acceptUpdateAccountingDifference');

		\preaccounting\InvoiceLib::updateAccountingDifference($data->e, POST('accountingDifference'));

		throw new ReloadAction();

	});

new Page()
->post('doImportInvoice', function($data) {

	$eInvoice = \preaccounting\ImportLib::getInvoiceById(POST('id', 'int'));

	if($eInvoice['cashflow']->notEmpty()) {
		$eInvoice['cashflow'] = \bank\CashflowLib::getById($eInvoice['cashflow']['id']);
	}

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
