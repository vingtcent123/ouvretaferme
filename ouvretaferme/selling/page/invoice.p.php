<?php
(new \selling\InvoicePage())
	->getCreateElement(function($data) {

		$eCustomer = \selling\CustomerLib::getById(INPUT('customer'));

		if($eCustomer->empty()) {

			$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

			throw new ViewAction($data, ':createCustomer');

		}

		$eCustomer->validate('canManage');

		$eFarm = $eCustomer['farm'];
		$eFarm['selling'] = \selling\ConfigurationLib::getByFarm($eFarm);
		$eFarm['selling']->isComplete() ?: throw new FailAction('selling\Configuration::notComplete', ['farm' => $eFarm]);

		return new \selling\Invoice([
			'customer' => $eCustomer,
			'farm' => $eFarm
		]);

	})
	->create(function($data) {

		$data->cSale = \selling\SaleLib::getForInvoice($data->e['customer'], GET('sales', 'array'));

		if(
			GET('more') or
			$data->cSale->empty()
		) {

			$data->search = new Search([
				'delivered' => GET('delivered', 'int', 60),
				'customer' => $data->e['customer'],
				'invoicing' => TRUE,
				'notId' => $data->cSale
			]);

			[$data->cSaleMore] = \selling\SaleLib::getByFarm($data->e['farm'], search: $data->search);

		} else {
			$data->search = new Search();
			$data->cSaleMore = new Collection();
		}

		$data->e['paymentCondition'] = $data->e['farm']['selling']['invoicePaymentCondition'];

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		if(POST('origin') === 'sales') {
			throw new ViewAction($data);
		} else {
			throw new RedirectAction(\farm\FarmUi::urlSellingSalesInvoice($data->e['farm']).'?success=selling:Invoice::created');
		}

	})
	->read('regenerate', function($data) {

		$data->cSale = \selling\SaleLib::getForInvoice($data->e['customer'], $data->e['sales'], checkInvoice: FALSE);

		if($data->cSale->count() !== count($data->e['sales'])) {
			throw new FailAction('selling\Invoice::inconsistencySales');
		}

		throw new ViewAction($data);

	}, validate: ['canWrite'])
	->write('doSend', function($data) {

		$eFarm = \farm\FarmLib::getById($data->e['farm']);
		$eFarm['selling'] = \selling\ConfigurationLib::getByFarm($eFarm);

		\selling\PdfLib::sendByInvoice($eFarm, $data->e);

		throw new ReloadAction('selling', 'Invoice::sent');

	})
	->doUpdate(function($data) {

		$data->e['cSale'] = \selling\SaleLib::getForInvoice($data->e['customer'], $data->e['sales'], checkInvoice: FALSE);

		if($data->e['cSale']->count() !== count($data->e['sales'])) {
			throw new FailAction('selling\Invoice::inconsistencySales');
		}

		\selling\InvoiceLib::generate($data->e);

		throw new ViewAction($data);

	}, propertiesUpdate: ['date', 'paymentCondition'], page: 'doRegenerate')
	->read('/facture/{id}', function($data) {

		$data->e->validate('canRead');

		$content = \selling\PdfLib::getContentByInvoice($data->e);
		$filename = $data->e->getInvoice().'-'.str_replace('-', '', $data->e['date']).'-'.$data->e['customer']['name'].'.pdf';

		throw new PdfAction($content, $filename);

	})
	->update()
	->quick(['paymentStatus', 'description'])
	->doUpdate(fn() => throw new ReloadAction('selling', 'Invoice::updated'))
	->doDelete(fn() => throw new ReloadAction('selling', 'Invoice::deleted'));
?>
