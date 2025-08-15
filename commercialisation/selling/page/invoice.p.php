<?php
new \selling\InvoicePage()
	->getCreateElement(function($data) {

		$eCustomer = \selling\CustomerLib::getById(INPUT('customer'));

		if($eCustomer->empty()) {

			$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

			throw new ViewAction($data, ':createCustomer');

		}

		$eCustomer->validate('canManage');

		$eFarm = $eCustomer['farm'];
		$eFarm->validateSellingComplete();

		return new \selling\Invoice([
			'customer' => $eCustomer,
			'farm' => $eFarm,
			'generation' => \selling\Invoice::NOW
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

		$data->e->merge([
			'paymentCondition' => $data->e['farm']->getSelling('invoicePaymentCondition'),
			'header' => $data->e['farm']->getSelling('invoiceHeader'),
			'footer' => $data->e['farm']->getSelling('invoiceFooter')
		]);

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

		throw new ViewAction($data);

	}, validate: ['canWrite', 'acceptRegenerate'])
	->write('doSend', function($data) {

		$eFarm = \farm\FarmLib::getById($data->e['farm']);

		$fw = new FailWatch();

		\selling\PdfLib::sendByInvoice($eFarm, $data->e);

		$fw->validate();

		throw new ReloadAction('selling', 'Invoice::sent');

	}, validate: ['canWrite', 'acceptSend'])
	->doUpdate(function($data) {

		$data->e['cSale'] = \selling\SaleLib::getForInvoice($data->e['customer'], $data->e['sales'], checkInvoice: FALSE);

		if($data->e['cSale']->count() !== count($data->e['sales'])) {
			throw new FailAction('selling\Invoice::inconsistencySales');
		}

		\selling\InvoiceLib::regenerate($data->e);

		throw new ViewAction($data);

	}, propertiesUpdate: ['date', 'paymentCondition', 'header', 'footer'], page: 'doRegenerate', validate: ['canWrite', 'acceptRegenerate'])
	->read('/facture/{id}', function($data) {

		if($data->e['content']->empty()) {
			throw new NotExistsAction();
		}

		$content = \selling\PdfLib::getContentByInvoice($data->e);
		$filename = new \selling\PdfUi()->getFilename(\selling\Pdf::INVOICE, $data->e['farm'], $data->e);

		throw new PdfAction($content, $filename);

	}, validate: ['canPublicRead'])
	->update(function($data) {

		$data->e['cPaymentMethod'] = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);

		throw new ViewAction($data);

	})
	->doUpdateProperties('doUpdatePaymentStatus', ['paymentStatus'], fn($data) => throw new ViewAction($data))
	->quick(['description'])
	->doUpdate(fn() => throw new ReloadAction('selling', 'Invoice::updated'))
	->doDelete(fn() => throw new ReloadAction('selling', 'Invoice::deleted'));

new Page(function($data) {

	$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');
	$data->eFarm->validateSellingComplete();

	})
	->get('createCollection', function($data) {

		$data->month = GET('month', '?string');
		$data->type = GET('type', '?string');

		if($data->month !== NULL) {
			$data->cSale = \selling\SaleLib::getForMonthlyInvoice($data->eFarm, $data->month, $data->type);
		}

		$data->e = new \selling\Invoice([
			'farm' => $data->eFarm,
			'paymentCondition' => $data->eFarm->getSelling('invoicePaymentCondition'),
			'header' => $data->eFarm->getSelling('invoiceHeader'),
			'footer' => $data->eFarm->getSelling('invoiceFooter')
		]);

		throw new ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$eInvoice = new \selling\Invoice();

		$fw = new FailWatch();

		$eInvoice->build(['date', 'paymentCondition', 'header', 'footer'], $_POST);

		$fw->validate();

		$cInvoice = \selling\InvoiceLib::buildCollectionForInvoice($data->eFarm, $eInvoice, POST('sales', 'array', []));

		\selling\InvoiceLib::createCollection($cInvoice);

		throw new RedirectAction(\farm\FarmUi::urlSellingSalesInvoice($data->eFarm).'?success=selling:Invoice::createdCollection');

	});

(new Page(function($data) {

		$data->c = \selling\InvoiceLib::getByIds(REQUEST('ids', 'array'));

		\selling\Invoice::validateBatch($data->c);


	}))
	->post('doSendCollection', function($data) {

		$data->c->validate('canWrite', 'acceptSend');

		$data->eFarm = \farm\FarmLib::getById($data->c->first()['farm']);

		foreach($data->c as $e) {
			\selling\PdfLib::sendByInvoice($data->eFarm, $e);
		}

		throw new ReloadAction('selling', 'Invoice::sentCollection');

	})
	->post('doDeleteCollection', function($data) {

		$data->c->validate('canDelete');

		\selling\InvoiceLib::deleteCollection($data->c);

		throw new ReloadAction('selling', 'Invoice::deletedCollection');

	});
?>
