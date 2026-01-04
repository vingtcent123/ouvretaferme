<?php
new \selling\InvoicePage()
	->getCreateElement(function($data) {

		$eCustomer = \selling\CustomerLib::getById(INPUT('customer'));

		if($eCustomer->empty()) {

			$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

			throw new ViewAction($data, ':createCustomer');

		}

		$eCustomer->validate('canManage', 'acceptInvoice');

		$eFarm = $eCustomer['farm'];
		$eFarm->validateLegal();

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

		$lastDate = \selling\InvoiceLib::getLastDate($data->e['farm']);

		$data->e->merge([
			'paymentCondition' => $data->e['farm']->getConf('invoicePaymentCondition'),
			'header' => $data->e['farm']->getConf('invoiceHeader'),
			'footer' => $data->e['farm']->getConf('invoiceFooter'),
			'lastDate' => $lastDate,
			'date' => max(currentDate(), $lastDate)
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
	->doUpdate(function($data) {

		$data->e['cSale'] = \selling\SaleLib::getForInvoice($data->e['customer'], $data->e['sales'], checkInvoice: FALSE);

		if($data->e['cSale']->count() !== count($data->e['sales'])) {
			throw new FailAction('selling\Invoice::inconsistencySales');
		}

		\selling\InvoiceLib::regenerate($data->e);

		throw new ViewAction($data);

	}, propertiesUpdate: ['dueDate', 'paymentCondition', 'header', 'footer'], page: 'doRegenerate', validate: ['canWrite', 'acceptRegenerate'])
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

	}, page: 'updatePayment', validate: ['canWrite', 'acceptUpdatePayment'])
	->update(page: 'updateComment', validate: ['canWrite'])
	->doUpdateProperties('doUpdateComment', ['comment'], fn() => throw new ReloadAction(), validate: ['canWrite'])
	->doUpdateProperties('doUpdatePayment', ['paymentMethod', 'paymentStatus'], fn($data) => throw new ReloadAction('selling', 'Invoice::updatedPayment'), validate: ['canWrite', 'acceptUpdatePayment'])
	->doUpdateProperties('doUpdatePaymentStatus', ['paymentStatus'], fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptUpdatePayment'])
	->quick(['comment'])
	->doDelete(fn() => throw new ReloadAction('selling', 'Invoice::deleted'));

new Page(function($data) {

	$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');
	$data->eFarm->validateLegal();

	})
	->get('createCollection', function($data) {

		$data->month = GET('month', '?string');
		$data->type = GET('type', '?string');

		if($data->month !== NULL) {
			$data->cSale = \selling\SaleLib::getForMonthlyInvoice($data->eFarm, $data->month, $data->type);
		}

		$data->cCustomerGroup = \selling\CustomerGroupLib::getByFarm($data->eFarm);

		$lastDate = \selling\InvoiceLib::getLastDate($data->eFarm);

		$data->e = new \selling\Invoice([
			'farm' => $data->eFarm,
			'paymentCondition' => $data->eFarm->getConf('invoicePaymentCondition'),
			'header' => $data->eFarm->getConf('invoiceHeader'),
			'footer' => $data->eFarm->getConf('invoiceFooter'),
			'lastDate' => $lastDate,
			'date' => max(currentDate(), $lastDate)
		]);

		throw new ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$eInvoice = new \selling\Invoice([
			'farm' => $data->eFarm
		]);

		$fw = new FailWatch();

		$eInvoice->build(['date', 'dueDate', 'paymentCondition', 'header', 'footer', 'status'], $_POST);

		$fw->validate();

		$cInvoice = \selling\InvoiceLib::buildCollectionForInvoice($data->eFarm, $eInvoice, POST('sales', 'array', []));

		\selling\InvoiceLib::createCollection($cInvoice);

		throw new RedirectAction(\farm\FarmUi::urlSellingSalesInvoice($data->eFarm).'?success=selling:Invoice::createdCollection');

	});

new Page(function($data) {

		$data->c = \selling\InvoiceLib::getByIds(REQUEST('ids', 'array'));

		\selling\Invoice::validateBatch($data->c);

	})
	->post('doUpdateConfirmedCollection', function($data) {

		$data->c->validate('canWrite', 'acceptStatusConfirmed');

		\selling\InvoiceLib::updateStatusCollection($data->c, \selling\Sale::CONFIRMED);

		throw new ReloadAction();

	})
	->post('doUpdatePaymentCollection', function($data) {

		$data->c->validate('canWrite', 'acceptUpdatePayment');

		$eMethod = \payment\MethodLib::getById(POST('paymentMethod'))->validate('canUse');

		\selling\InvoiceLib::updatePaymentCollection(
			$data->c, [
				'paymentMethod' => $eMethod,
				'paymentStatus' => new Sql('IF(paymentStatus IS NULL, "'.\selling\Invoice::NOT_PAID.'", paymentStatus)')
			]
		);

		throw new ReloadAction();

	})
	->post('doUpdateCanceledCollection', function($data) {

		$data->c->validate('canWrite', 'acceptStatusCanceled');

		\selling\InvoiceLib::updateStatusCollection($data->c, \selling\Sale::CANCELED);

		throw new ReloadAction();

	})
	->post('doSendCollection', function($data) {

		$data->c->validate('canWrite', 'acceptSend');

		$data->eFarm = \farm\FarmLib::getById($data->c->first()['farm']);

		$fw = new FailWatch();

		foreach($data->c as $e) {
			\selling\PdfLib::sendByInvoice($data->eFarm, $e);
		}

		$fw->validate();

		throw new ReloadAction('selling', $data->c->count() > 1 ? 'Invoice::sentCollection' : 'Invoice::sent');

	})
	->post('doDeleteCollection', function($data) {

		$data->c->validate('canDelete');

		\selling\InvoiceLib::deleteCollection($data->c);

		throw new ReloadAction('selling', 'Invoice::deletedCollection');

	})
	->post('doUpdateRefuseReadyForAccountingCollection', function($data) {

		$data->c->validate('canWrite');

		if($data->c->notEmpty()) {

			$data->c->first()['farm']->validate('hasAccounting');

			\selling\InvoiceLib::updateReadyForAccountingCollection($data->c, NULL);

			throw new ReloadAction('selling', 'Invoice::readyForAccountingRefused');

		}

		throw new VoidAction($data);

	});
?>
