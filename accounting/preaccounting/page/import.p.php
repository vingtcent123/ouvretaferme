<?php
new \selling\PaymentPage()
	->write('updateInvoiceAccountingDifference', function($data) {

		if(in_array(POST('accountingDifference'), [\selling\Payment::AUTOMATIC, \selling\Payment::NOTHING]) === FALSE) {
			throw new ReloadAction();
		}

		if($data->e['cashflow']->empty()) {
			throw new NotExpectedAction('Invoice does not accept accountingDifference update.');
		}

		$data->e['cashflow'] = \bank\CashflowLib::getById($data->e['cashflow']['id']);

		$priceIncludingVat = match($data->e['source']) {
			\selling\Payment::INVOICE => \selling\Invoice::model()->select('priceIncludingVat')->whereId($data->e['invoice']['id'])->get()['priceIncludingVat'],
			\selling\Payment::SALE => \selling\Sale::model()->select('priceIncludingVat')->whereId($data->e['sale']['id'])->get()['priceIncludingVat'],
		};

		$totalPaid = \selling\PaymentLib::sumTotalPaid($data->e);
		if($totalPaid === $priceIncludingVat and $data->e['cashflow']['amount'] === $data->e['amountIncludingVat']) {
			throw new NotExpectedAction();
		}

		\preaccounting\PaymentLib::updateAccountingDifference($data->e, POST('accountingDifference'));

		throw new ReloadAction();

	});

new Page()
->post('doImportPayment', function($data) {

	$ePayment = \preaccounting\ImportLib::getPaymentById(POST('id', 'int'));

	$ePayment->validate('acceptAccountingImport');

	$fw = new FailWatch();

	\preaccounting\ImportLib::importPayment($data->eFarm, $ePayment);

	$fw->validate();

	\account\LogLib::save('import', 'Payment', ['id' => $ePayment['id']]);

	throw new ReloadAction('preaccounting', 'Payment::imported');

})
->post('doIgnorePayment', function($data) {

	$ePayment = \selling\PaymentLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\preaccounting\ImportLib::ignorePayment($ePayment);

	\account\LogLib::save('ignore', 'Payment', ['id' => $ePayment['id']]);

	throw new ReloadAction('preaccounting', 'Payment::ignored');
})
->get('importPaymentCollection', function($data) {

	throw new ViewAction($data);

})
->post('doImportPaymentCollection', function($data) {

	$cPayment = \preaccounting\ImportLib::getPaymentsByIds(POST('ids', 'array'));

	\selling\Payment::validateBatch($cPayment);

	\preaccounting\ImportLib::importPayments($data->eFarm, $cPayment);

	\account\LogLib::save('importSeveral', 'Payment', ['ids' => $cPayment->getIds()]);

	throw new ReloadAction('preaccounting', $cPayment->count() > 1 ? 'Payment::importedSeveral' : 'Payment::imported');

})
->post('doIgnoreCollection', function($data) {

	$cPayment = \selling\PaymentLib::getByIds(POST('ids', 'array'))->validate('acceptAccountingIgnore');

	\selling\Payment::validateBatchIgnore($cPayment);
	\preaccounting\ImportLib::ignorePayments($cPayment);

	\account\LogLib::save('ignoreSeveral', 'Payment', ['ids' => $cPayment->getIds()]);

	throw new ReloadAction('preaccounting', 'Payment::ignoredSeveral');

})
;

?>
