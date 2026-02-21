<?php
new \invoicing\InvoicePage()
	->read('/facturation-electronique/facture/{id}', function($data) {

		$data->e['cLine'] = \invoicing\LineLib::getByInvoice($data->e);
		$data->e['cEvent'] = \invoicing\EventLib::getByInvoice($data->e);

		throw new ViewAction($data);

	})
;
