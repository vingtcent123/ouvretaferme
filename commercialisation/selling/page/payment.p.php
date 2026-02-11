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

?>
