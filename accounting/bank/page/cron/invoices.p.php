<?php
new Page()
	->cron('index', function($data) {

		\bank\CashflowInvoiceLib::associateInvoicesToCashflow();

	}, interval: '5 */'.\bank\CashflowInvoiceLib::DELAY_ASSOCIATE_INVOICES.' * * *');
?>
