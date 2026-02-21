<?php
new Page()
->get('/facturation-electronique/ventes/', function($data) {

	[$data->cInvoice, $data->nInvoice] = \invoicing\InvoiceLib::getAll(new Search(['direction' => \invoicing\Invoice::OUT]));

	throw new ViewAction($data);

});
?>
