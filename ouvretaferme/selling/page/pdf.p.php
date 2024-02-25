<?php
(new Page())
	->get('getLabels', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canRemote');

		$sales = GET('sales', 'array');

		if($sales) {
			$data->cSale = \selling\SaleLib::getForLabelsByIds($data->eFarm, $sales, selectItems: TRUE);
		} else {
			$data->cSale = new Collection();
		}

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->get('getDocument', function($data) {

		$data->type = GET('type', [\selling\Pdf::DELIVERY_NOTE, \selling\Pdf::ORDER_FORM, \selling\Pdf::INVOICE], fn() => throw new NotExpectedAction());
		$data->e = \selling\SaleLib::getById(GET('id'))->validate('canRemote', fn($e) => $e->canDocument($data->type));

		$data->e['customer']['user'] = \user\UserLib::getById($data->e['customer']['user']); // Récupération de l'e-mail

		if($data->type === \selling\Pdf::INVOICE) {
			$data->e['invoice'] = \selling\InvoiceLib::getById($data->e['invoice']);
			if($data->e['invoice']->empty()) {
				throw new NotExpectedAction('No invoice');
			}
		}

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);
		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm)->validate('isComplete');

		$data->cItem = \selling\SaleLib::getItems($data->e);

		throw new ViewAction($data);

	});

(new \selling\InvoicePage())
	->read('getDocumentInvoice', function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);
		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm)->validate('isComplete');

		$data->e['customer']['user'] = \user\UserLib::getById($data->e['customer']['user']); // Récupération de l'e-mail

		$data->cSale = \selling\SaleLib::getForInvoice($data->e['customer'], $data->e['sales'], checkInvoice: FALSE);

		throw new ViewAction($data);

	}, validate: ['canRemote']);
?>
