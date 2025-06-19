<?php
new \selling\PaymentPage()
	->applyElement(function($data, \selling\Payment $e) {

		$e->expects(['id', 'sale']);
		$eSale = \selling\SaleLib::getById($e['sale']['id']);
		$eSale->validate('acceptUpdateMarketSalePayment');

})
	->quick(['amountIncludingVat']);
?>
