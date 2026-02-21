<?php
new Page()
->get('/facturation-electronique/achats/', function($data) {

	[$data->cInvoice, $data->nInvoice] = \invoicing\InvoiceLib::getAll(new Search(['direction' => \invoicing\Invoice::IN]));

	throw new ViewAction($data);

});
?>
