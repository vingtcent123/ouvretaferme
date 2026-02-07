<?php
new Page()
	->remote('getLabels', 'selling', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('id'));

		$sales = GET('sales', 'array');

		if($sales) {
			$data->cSale = \selling\SaleLib::getForLabelsByIds($data->eFarm, $sales, selectItems: TRUE);
		} else {
			$data->cSale = new Collection();
		}

		throw new ViewAction($data);

	})
	->remote('getDocument', 'selling',  function($data) {

		$data->type = GET('type', [\selling\Pdf::DELIVERY_NOTE, \selling\Pdf::ORDER_FORM], fn() => throw new NotExpectedAction());
		$data->ePdf = \selling\PdfLib::getById(GET('id'))->validate();

		$data->e = \selling\SaleLib::getById($data->ePdf['sale']);
		$data->e['customer']['user'] = \user\UserLib::getById($data->e['customer']['user']); // Récupération de l'e-mail

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);
		$data->eFarm->validateLegal();

		$data->cItem = \selling\SaleLib::getItemsForDocument($data->e, $data->type);

		throw new ViewAction($data);

	});

new \selling\PdfPage()
	->read('/pdf/{id}', function($data) {

		if(in_array($data->e['type'], [\selling\Pdf::ORDER_FORM, \selling\Pdf::DELIVERY_NOTE]) === FALSE) {
			throw new NotExpectedAction();
		}

		$content = \selling\PdfLib::getContent($data->e);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$eSale = \selling\SaleLib::getById($data->e['sale']);

		$filename = new \selling\PdfUi()->getFilename($data->e['type'], $data->e['farm'], $eSale);

		throw new PdfAction($content, $filename);


	});

new \selling\InvoicePage()
	->remote('getDocumentInvoice', 'selling', function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		$data->e['customer']['user'] = \user\UserLib::getById($data->e['customer']['user']); // Récupération de l'e-mail

		$data->cSale = \selling\SaleLib::getForInvoice($data->e['customer'], $data->e['sales'], checkInvoice: FALSE);

		throw new ViewAction($data);

	});
?>
