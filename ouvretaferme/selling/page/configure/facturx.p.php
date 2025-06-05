<?php
new Page()
	->get('index', function($data) {

		$eInvoice = \selling\InvoiceLib::getById(2119);
		$newPdf = \selling\FacturXLib::generate($eInvoice, 'tot');
		file_put_contents('/tmp/invoice.pdf', $newPdf);

	});
?>
