<?php
new \selling\SalePage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validateVerified();

		if(input_exists('compositionOf')) {
			$profile = \selling\Sale::COMPOSITION;
		} else if(INPUT('market', 'bool')) {
			$profile = \selling\Sale::MARKET;
		} else {
			$profile = \selling\Sale::SALE;
		}

		$eSale = new \selling\Sale([
			'farm' => $data->eFarm,
			'profile' => $profile,
			'shop' => new \shop\Shop(),
			'shopDate' => new \shop\Date(),
		]);

		if($eSale->isComposition()) {
			$eSale['compositionOf'] = \selling\ProductLib::getCompositionById(INPUT('compositionOf'))->validateProperty('farm', $data->eFarm);
		}

		return $eSale;

	})
	->create(function($data) {

		if($data->e->isComposition()) {

			$data->e['shopProducts'] = FALSE;
			$data->e['customer'] = new \selling\Customer();
			$data->e['type'] = $data->e['compositionOf']['private'] ? \selling\Sale::PRIVATE : \selling\Sale::PRO;
			$data->e['discount'] = 0;
			$data->e['cProduct'] = \selling\ProductLib::getForSale($data->e['farm'], $data->e['type'], excludeComposition: TRUE);

		} else {

			$data->e['customer'] = get_exists('customer') ? \selling\CustomerLib::getById(GET('customer'))->validateProperty('farm', $data->e['farm']) : new \selling\Customer();

			\selling\SaleLib::fillForCreate($data->e);

		}

		if(
			$data->e['customer']->notEmpty() or
			$data->e->isComposition()
		) {

			$data->e['hasVat'] = $data->e['farm']->getConf('hasVat');
			$data->e['taxes'] = $data->e->getTaxesFromType();

			$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm'], index: 'id');

			$data->e['nGrid'] = \selling\ProductLib::generateItemsByCustomer($data->e['cProduct'], $data->e['customer'], $data->e);

		} else {
			$data->e['nGrid'] = 0;
		}

		throw new \ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(
			$data->e->isComposition() ?
				\selling\ProductUi::url($data->e['compositionOf']).'?success=Product::createdComposition' :
				\selling\SaleUi::url($data->e).'?success=Sale::created'
		);
	});


new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))
			->validateVerified()
			->validate('canSelling');

	})
	->get('createCollection', function($data) {

		$eCustomer = new \selling\Customer();
		$cCustomer = new Collection();

		if(get_exists('group')) {

			$eCustomerGroup = \selling\CustomerGroupLib::getById(GET('group'))->validateProperty('farm', $data->eFarm);
			$cCustomer = \selling\CustomerLib::getByGroup($eCustomerGroup);

			if($cCustomer->notEmpty()) {
				$eCustomer = $cCustomer->first();
			}

			$sourceType = 'group';
			$sourceValue = $eCustomerGroup;

		} else if(get_exists('customers')) {

			$cCustomer = \selling\CustomerLib::getByIds(GET('customers', 'array'));

			\selling\Customer::validateCreateSale($cCustomer, $data->eFarm);

			if($cCustomer->notEmpty()) {
				$eCustomer = $cCustomer->first();
			}

			$sourceType = 'customers';
			$sourceValue = $cCustomer;

		} else if(get_exists('customer')) {

			$eCustomer = \selling\CustomerLib::getById(GET('customer'))->validateProperty('farm', $data->eFarm);

			if($eCustomer->notEmpty()) {
				$cCustomer[] = $eCustomer;
			}

			$sourceType = 'customer';
			$sourceValue = $eCustomer;

		} else {
			$sourceType = NULL;
			$sourceValue = NULL;
		}

		if(
			$cCustomer->count() === 1 and
			$eCustomer['destination'] === \selling\Customer::COLLECTIVE
		) {
			throw new RedirectAction('/selling/sale:create?farm='.$data->eFarm['id'].'&customer='.$eCustomer['id'].'');
		}

		$data->e = new \selling\Sale([
			'farm' => $data->eFarm,
			'profile' => \selling\Sale::SALE,
			'customer' => $eCustomer,
			'cCustomer' => $cCustomer
		]);

		$data->e->validate('canCreate');

		\selling\SaleLib::fillForCreate($data->e);

		if($cCustomer->notEmpty()) {

			$data->e['type'] = $eCustomer['type'];
			$data->e['discount'] = $eCustomer['discount'];

			$data->e['hasVat'] = $data->e['farm']->getConf('hasVat');
			$data->e['taxes'] = $data->e->getTaxesFromType();

			$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm'], index: 'id');

			$data->cCustomerGroup = new Collection();

		} else {

			$data->cCustomerGroup = \selling\CustomerGroupLib::getByFarm($data->eFarm);

		}

		$data->e['gridSource'] = $sourceType;
		$data->e['gridValue'] = $sourceValue;

		switch($sourceType) {

			case 'customers' :
				$data->e['nGrid'] = \selling\ProductLib::generateItemsByCustomers($data->e['cProduct'], $sourceValue, $data->e);
				break;

			case 'customer' :
				$data->e['nGrid'] = \selling\ProductLib::generateItemsByCustomer($data->e['cProduct'], $sourceValue, $data->e);
				break;

			case 'group' :
				$data->e['nGrid'] = \selling\ProductLib::generateItemsByGroup($data->e['cProduct'], $sourceValue, $data->e);
				break;

		}


		throw new \ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$eSaleReference = new \selling\Sale([
			'farm' => $data->eFarm,
			'profile' => \selling\Sale::SALE,
		]);

		$eSaleReference->validate('canCreate');

		$fw = new \FailWatch();

		$properties = \selling\SaleLib::getPropertiesCreate()($eSaleReference);

		$cSale = new Collection();
		$type = NULL;

		foreach(POST('customers', 'array') as $customer) {

			$eSale = clone $eSaleReference;

			$eSale->build($properties, ['customer' => $customer] + $_POST, new \Properties('create'));

			$type ??= $eSale['type'];

			if($type !== $eSale['type']) {
				\selling\Sale::fail('customer.typeConsistency');
			}

			$fw->validate();

			$cSale[] = $eSale;

		}

		if($cSale->empty()) {
			\selling\Sale::fail('customer.check');
			$fw->validate();
		}

		\selling\Sale::model()->beginTransaction();

			foreach($cSale as $eSale) {
				\selling\SaleLib::create($eSale);
			}


		\selling\Sale::model()->commit();

		$fw->validate();

		throw new RedirectAction(
			$cSale->count() > 1 ?
				\farm\FarmUi::urlSellingSales($data->eFarm, \farm\Farmer::ALL).'?success=selling\\Sale::createdCollection' :
				\selling\SaleUi::url($eSale).'?success=Sale::created'
		);

	});

new \selling\SalePage()
	->doUpdate(function($data) {

		throw new ReloadAction('selling', $data->e->isComposition() ? 'Product::updatedComposition' : 'Sale::updated');

	});

new \selling\SalePage()
	->read('/vente/{id}', function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		if($data->e->isMarketSale()) {
			throw new NotExpectedAction('Market sale');
		}

		$data->cItem = \selling\SaleLib::getItems($data->e, withIngredients: TRUE);
		$data->ccSaleMarket = \selling\SaleLib::getByParent($data->e);
		$data->cHistory = \selling\HistoryLib::getBySale($data->e);
		$data->ccPdf = \selling\PdfLib::getBySale($data->e);

		$data->e['invoice'] = \selling\InvoiceLib::getById($data->e['invoice'], properties: \selling\InvoiceElement::getSelection());
		$data->e['shopPoint'] = \shop\PointLib::getById($data->e['shopPoint']);

		if(get_exists('prepare')) {
			\selling\PreparationLib::start($data->eFarm, GET('prepare'));
		}

		$data->preparing = \selling\PreparationLib::getPreparing($data->eFarm, $data->e);

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);

		if($data->preparing) {
			throw new ViewAction($data, ':salePreparing');
		} else {
			throw new ViewAction($data, $data->eFarm->canSelling() ? ':salePlain' :  ':salePanel');
		}

	})
	->read('generateOrderForm', function($data) {

		$data->e->validate('canManage');
		$data->e->acceptDocumentTarget($data->e['type']) ?: throw new FailAction('farm\Farm::disabled');
		$data->e->acceptGenerateOrderForm() ?: throw new FailAction('selling\Sale::generateOrderForm');

		$data->eFarm = $data->e['farm'];
		$data->eFarm->validateLegal();

		$data->e['orderFormPaymentCondition'] ??= $data->eFarm->getConf('orderFormPaymentCondition');
		$data->e['orderFormHeader'] ??= $data->eFarm->getConf('orderFormHeader');
		$data->e['orderFormFooter'] ??= $data->eFarm->getConf('orderFormFooter');

		throw new ViewAction($data);

	})
	->read('generateDeliveryNote', function($data) {

		$data->e->validate('canManage');
		$data->e->acceptDocumentTarget($data->e['type']) ?: throw new FailAction('farm\Farm::disabled');
		$data->e->acceptGenerateDeliveryNote() ?: throw new FailAction('selling\Sale::generateDeliveryNote');

		$data->eFarm = $data->e['farm'];
		$data->eFarm->validateLegal();

		$data->e['deliveryNoteDate'] ??= $data->e['deliveredAt'];
		$data->e['deliveryNoteHeader'] ??= $data->eFarm->getConf('deliveryNoteHeader');
		$data->e['deliveryNoteFooter'] ??= $data->eFarm->getConf('deliveryNoteFooter');

		throw new ViewAction($data);

	})
	->write('doGenerateDocument', function($data) {

		if($data->e['items'] === 0) {
			throw new FailAction('selling\Pdf::emptySale');
		}

		$data->e->acceptDocumentTarget($data->e['type']) ?: throw new FailAction('farm\Farm::disabled');

		$data->e['farm']->validateLegal();

		$type = POST('type', [\selling\Pdf::DELIVERY_NOTE, \selling\Pdf::ORDER_FORM], fn() => throw new NotExpectedAction());

		if($data->e->canDocument($type) === FALSE) {
			throw new NotAllowedAction();
		}

		$fw = new FailWatch();

		switch($type) {

			case \selling\Pdf::DELIVERY_NOTE:

				$data->e->acceptGenerateDeliveryNote() ?: throw new FailAction('selling\Sale::generateDeliveryNote');

				$properties = ['deliveryNoteDate', 'deliveryNoteHeader', 'deliveryNoteFooter'];

				$data->e->build($properties, $_POST);

				$fw->validate();

				\selling\Sale::model()
					->select($properties)
					->update($data->e);

				break;

			case \selling\Pdf::ORDER_FORM :

				$data->e->acceptGenerateOrderForm() ?: throw new FailAction('selling\Sale::generateOrderForm');

				$properties = ['orderFormValidUntil', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter'];

				$data->e->build($properties, $_POST);

				$fw->validate();

				\selling\Sale::model()
					->select($properties)
					->update($data->e);

				break;

		};

		$data->ePdf = \selling\PdfLib::generateBusiness($type, $data->e);

		throw new ViewAction($data);
	})
	->write('doSendDocument', function($data) {

		$eFarm = \farm\FarmLib::getById($data->e['farm']);

		$data->type = POST('type', [\selling\Pdf::ORDER_FORM, \selling\Pdf::DELIVERY_NOTE], fn($value) => throw new NotExpectedAction());

		if($data->e->canDocument($data->type) === FALSE) {
			throw new NotAllowedAction();
		}

		$fw = new FailWatch();

		if($data->e['customer']['user']->notEmpty()) {
			$data->e['customer']['user'] = \user\UserLib::getById($data->e['customer']['user']);
		}

		\selling\PdfLib::sendBySale($eFarm, $data->e, $data->type);

		$fw->validate();

		throw new ReloadAction('selling', match($data->type) {
			\selling\Pdf::ORDER_FORM =>'Pdf::orderFormSent',
			\selling\Pdf::DELIVERY_NOTE =>'Pdf::deliveryNoteSent'
		});
	})
	->quick(['deliveredAt', 'shipping'], validate: ['canUpdate', 'isOpen'])
	->update(function($data) {

		$data->e['cPoint'] = \shop\PointLib::getAlphabeticalByFarm($data->e['farm']);

		throw new ViewAction($data);

	})
	->update(function($data) {

		$data->e['cPaymentMethod'] = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);

		throw new ViewAction($data);

	}, page: 'updatePayment')
	->doUpdateProperties('doUpdatePayment', ['paymentMethod', 'paymentStatus', 'paidAt'], fn($data) => throw new ReloadAction('selling', 'Sale::updatedPayment'), validate: ['canWrite', 'acceptUpdatePayment'])
	->read('updateShop', function($data) {

		$data->e['cShop'] = \shop\ShopLib::getAroundByFarm($data->e['farm'], $data->e['type']);

		throw new ViewAction($data);

	}, validate: ['canWrite', 'acceptAssociateShop'])
	->write('doAssociateShop', function($data) {
		
		$data->e->validate('acceptAssociateShop');
		\selling\SaleLib::associateShop($data->e, $_POST);

		throw new ReloadAction('selling', 'Sale::updated');

	})
	->write('doDissociateShop', function($data) {

		$data->e->validate('acceptDissociateShop');
		\selling\SaleLib::dissociateShop($data->e);

		throw new ReloadAction('selling', 'Sale::updated');

	})
	->read('updateCustomer', function($data) {

		throw new ViewAction($data);

	}, validate: ['canUpdateCustomer', 'acceptUpdateCustomer'])
	->write('doUpdateCustomer', function($data) {

		$fw = new FailWatch();

		$data->e->build(['customer'], $_POST, new \Properties('update'));

		$fw->validate();

		\selling\SaleLib::updateCustomer($data->e, $data->e['customer']);

		throw new ReloadAction('selling', 'Sale::customerUpdated');

	}, validate: ['canUpdateCustomer', 'acceptUpdateCustomer'])
	->write('doUpdateNeverPaid', function($data) {

		\selling\SaleLib::updateNeverPaid($data->e);

		throw new ReloadAction();

	}, validate: ['canWrite', 'acceptUpdatePayment'])
	->write('doDeletePayment', function($data) {

		\selling\SaleLib::deletePayment($data->e);

		throw new ReloadLayerAction();

	}, validate: ['canWrite', 'acceptUpdatePayment'])
	->read('duplicate', function($data) {

		if($data->e->acceptDuplicate() === FALSE) {
			throw new NotExpectedAction('Can duplicate');
		}

		throw new ViewAction($data);

	})
	->write('doDuplicate', function($data) {

		if($data->e->acceptDuplicate() === FALSE) {
			throw new NotExpectedAction('Can duplicate');
		}

		$fw = new \FailWatch();

		$data->e->build(['deliveredAt', 'preparationStatus'], $_POST, new \Properties('create'));

		$fw->validate();

		$data->eSaleNew = \selling\SaleLib::duplicate($data->e);

		throw new RedirectAction(\selling\SaleUi::url($data->eSaleNew).'?success=series\\Series::duplicated');
	})
	->write('doDelete', function($data) {

		$fw = new \FailWatch();

		if(
			$data->e->acceptDeleteStatus() === FALSE or
			$data->e->acceptDeletePaymentStatus() === FALSE
		) {
			\selling\Sale::fail('deletedNotDraft');
		} else if($data->e->acceptDeleteMarketSale() === FALSE) {
			\selling\Sale::fail('deletedMarketSale');
		}

		$fw->validate();

		\selling\SaleLib::delete($data->e);

		throw new RedirectAction(
			$data->e->isComposition() ?
				\selling\ProductUi::url($data->e['compositionOf']).'?success=selling\\Product::deletedComposition' :
				\farm\FarmUi::urlSellingSalesAll($data->e['farm']).'?success=selling\\Sale::deleted'
		);

	});

new Page(function($data) {

		$data->c = \selling\SaleLib::getByIds(REQUEST('ids', 'array'));

		\selling\Sale::validateBatch($data->c);

		$data->eFarm = $data->c->first()['farm'];

	})
	->post('doExportCollection', function($data) {

		$data->c->validate('canRead');

		$filename = 'sales.pdf';
		$content = \selling\PdfLib::build('/selling/sale:getExport?ids[]='.implode('&ids[]=', $data->c->getIds()), $filename);

		throw new PdfAction($content, $filename);

	})
	->remote('getExport', 'selling', function($data) {

		\selling\SaleLib::fillForExport($data->c);

		$data->cItem = \selling\ItemLib::getSummaryBySales($data->c);

		throw new ViewAction($data);

	})
	->post('doUpdateCanceledCollection', function($data) {

		$data->c->validate('canWrite', 'acceptStatusCanceled');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::CANCELED);

		throw new ReloadAction();

	})
	->post('doUpdateDeliveredCollection', function($data) {

		$data->c->validate('canWrite', 'acceptStatusDelivered');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::DELIVERED);

		throw new ReloadAction();

	})
	->post('doUpdatePreparedCollection', function($data) {

		$data->c->validate('canWrite', 'acceptStatusPrepared');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::PREPARED);

		throw new ReloadAction();

	})
	->post('doUpdateConfirmedCollection', function($data) {

		$data->c->validate('canWrite', 'acceptStatusConfirmed');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::CONFIRMED);

		throw new ReloadAction();

	})
	->post('doUpdateSellingCollection', function($data) {

		$data->c->validate('canWrite', 'acceptStatusSelling');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::SELLING);

		throw new ReloadAction();

	})
	->post('doUpdateDraftCollection', function($data) {

		$data->c->validate('canWrite', 'acceptStatusDraft');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::DRAFT);

		throw new ReloadAction();

	})
	->post('doDeleteCollection', function($data) {

		$data->c->validate('canDelete', 'acceptDelete');

		\selling\SaleLib::deleteCollection($data->c);

		throw new ReloadAction();

	})
	->post('doUpdatePaymentCollection', function($data) {

		$data->c->validate('canWrite', 'acceptUpdatePayment');

		$eMethod = \payment\MethodLib::getById(POST('paymentMethod'));

		if($eMethod->notEmpty()) {
			$eMethod->validate('canUse', 'acceptManualUpdate');
		}

		\selling\SaleLib::updatePaymentMethodCollection($data->c, $eMethod);

		if($data->eFarm->hasAccounting()) {
			\preaccounting\SaleLib::setReadyForAccountingSaleCollection($data->c);
		}

		throw new ReloadAction('selling', 'Sale::paymentMethodUpdated');

	})
	->post('doUpdatePaymentStatusCollection', function($data) {

		$data->c->validate('canWrite', 'acceptUpdatePaymentStatus');

		$paymentStatus = POST('paymentStatus', [\selling\Sale::PAID, \selling\Sale::NOT_PAID]);

		\selling\SaleLib::updatePaymentStatusCollection($data->c, $paymentStatus);

		throw new ReloadAction('selling', 'Sale::paymentStatusUpdated');

	})
	->post('doUpdateRefuseReadyForAccountingCollection', function($data) {

		$data->c->validate('canWrite');
		$data->eFarm->validate('hasAccounting');

		\selling\SaleLib::updateReadyForAccountingCollection($data->c, NULL);

		throw new ReloadAction('selling', 'Sale::readyForAccountingRefused');

	});

new \selling\PdfPage()
	->doDelete(fn($data) => throw new ReloadAction('selling', 'Pdf::deleted'), page: 'doDeleteDocument');

new \farm\FarmPage()
	->write('downloadLabels', function($data) {

		if(POST('checkSales', 'bool')) {

			$data->cSale = \selling\SaleLib::getForLabelsByIds($data->e, POST('sales', 'array'));

			if($data->cSale->empty()) {
				throw new RedirectAction(\farm\FarmUi::urlSellingSalesLabel($data->e).'?error=selling\\Sale::downloadEmpty');
			}

			$data->cSale->validate('canUpdate');

		} else {
			$data->cSale = new Collection();
		}


		$filename = $data->cSale->empty() ?
			'labels-empty.pdf' :
			'labels-'.implode('-', $data->cSale->getIds()).'.pdf';

		$data->content = \selling\PdfLib::buildLabels($data->e, $data->cSale, $filename);

		throw new PdfAction($data->content, $filename);

	});
?>
