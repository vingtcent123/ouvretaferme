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

		$totalPaid = \selling\PaymentAccountingLib::sumTotalPaid($data->e);
		if($totalPaid === $priceIncludingVat and $data->e['cashflow']['amount'] === $data->e['amountIncludingVat']) {
			throw new NotExpectedAction();
		}

		\preaccounting\PaymentLib::updateAccountingDifference($data->e, POST('accountingDifference'));

		throw new ReloadAction();

	})
	->write('doIgnorePayment', function($data) {

		\preaccounting\ImportLib::ignorePayment($data->e);

		\account\LogLib::save('ignore', 'Payment', ['id' => $data->e['id']]);

		throw new ReloadAction('preaccounting', 'Payment::ignored');

	}, validate: ['acceptAccountingIgnore'])
;

new Page()
	->post('doImport', function($data) {

		$from = POST('from');
		$months = \preaccounting\PreaccountingLib::extractMonths($data->eFarm['eFinancialYear']);

		if(
			mb_strlen($from) !== 10 or
			\util\DateLib::isValid($from) === FALSE or
			in_array(mb_substr($from, 0, 7), $months) === FALSE or
			$from >= date('Y-m-01')
		) {
			throw new NotExistsAction();
		}

		$search = new Search([
			'from' => $from,
			'to' => date('Y-m-t', strtotime(mb_substr($from, 0, 7).'-01')),
			'cRegister' => \cash\RegisterLib::getAll(),
			'cMethod' => \payment\MethodLib::getByFarm($data->eFarm, online: FALSE, onlyActive: FALSE),
		]);

		\preaccounting\ImportLib::importCollection($data->eFarm, $search);

		\account\LogLib::save('import', 'FinancialYear', ['date' => $from]);

		throw new RedirectAction(\farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite?success=preaccounting\\Preaccounting::imported');

	})
;

?>
