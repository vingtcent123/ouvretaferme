<?php
new Page()
	->get('index', function($data) {

		$eInvoice = \selling\InvoiceLib::getById(1971);
		\selling\FacturXLib::generate($eInvoice);

	});
?>
