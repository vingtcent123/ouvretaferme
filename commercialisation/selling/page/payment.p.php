<?php
new \selling\PaymentPage()
	->applyElement(function($data, \selling\Payment $e) {

			$e->expects(['id', 'sale']);

			$e['sale'] = \selling\SaleLib::getById($e['sale']);
			$e['sale']->validate('acceptUpdateMarketSalePayment');

	})
	->quick(['amountIncludingVat']);

new \selling\PaymentPage()
	->doDelete(fn($data) => throw new ReloadAction('selling', 'Payment::deleted'));


new Page(function($data) {

		$data->c = \selling\PaymentLib::getByIds(REQUEST('ids', 'array'));

		\selling\Payment::validateBatch($data->c);

	})

	->post('doUpdateRefuseReadyForAccountingCollection', function($data) {

		$data->c->validate('canWrite');

		if($data->c->notEmpty()) {

			$data->c->first()['farm']->validate('hasAccounting');

			\selling\PaymentLib::updateReadyForAccountingCollection($data->c, NULL);

			throw new ReloadAction('selling', 'Payment::readyForAccountingRefused');

		}

		throw new VoidAction($data);

	})
?>
