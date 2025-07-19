<?php
new \selling\SalePage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		if(input_exists('compositionOf')) {
			$origin = \selling\Sale::COMPOSITION;
		} else if(INPUT('market', 'bool')) {
			$origin = \selling\Sale::MARKET;
		} else {
			$origin = \selling\Sale::SALE;
		}

		$eSale = new \selling\Sale([
			'farm' => $data->eFarm,
			'origin' => $origin,
		]);

		if($eSale->isComposition()) {
			$eSale['compositionOf'] = \selling\ProductLib::getCompositionById(INPUT('compositionOf'))->validateProperty('farm', $data->eFarm);
		}

		return $eSale;

	})
	->create(function($data) {

		if($data->e->isComposition()) {

			$data->e['shop'] = new \shop\Shop();
			$data->e['shopDate'] = new \shop\Date();
			$data->e['customer'] = new \selling\Customer();
			$data->e['type'] = $data->e['compositionOf']['private'] ? \selling\Sale::PRIVATE : \selling\Sale::PRO;
			$data->e['discount'] = 0;

		} else {

			$eDate = get_exists('shopDate') ? \shop\DateLib::getById(GET('shopDate'), \shop\Date::getSelection() + ['shop' => ['shared']])->validateProperty('farm', $data->eFarm)->validate('acceptOrder', 'acceptNotShared') : new \shop\Date();

			$data->e->merge([
				'shopDate' => $eDate,
				'shop' => $eDate->empty() ? new \shop\Shop() : $eDate['shop']
			]);

			$data->e['customer'] = get_exists('customer') ? \selling\CustomerLib::getById(GET('customer'))->validateProperty('farm', $data->eFarm) : new \selling\Customer();

			if($data->e['customer']->notEmpty()) {
				$data->e['type'] = $data->e['customer']['type'];
				$data->e['discount'] = $data->e['customer']['discount'];
			}

		}

		if(
			$data->e['customer']->notEmpty() or
			$data->e->isComposition()
		) {

			$data->e['hasVat'] = $data->e['farm']->getSelling('hasVat');
			$data->e['taxes'] = $data->e->getTaxesFromType();

			$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm'], index: 'id');

			$data->e['cProduct'] = $data->e['shopDate']->empty() ?
				\selling\ProductLib::getForSale($data->e['farm'], $data->e['type'], excludeComposition: $data->e->isComposition()) :
				\shop\ProductLib::getByDate($data->e['shopDate'], $data->e['customer'], public: TRUE)->getColumnCollection('product');

			\selling\ProductLib::applyItemsForSale($data->e['cProduct'], $data->e);

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


(new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canSelling');

	}))
	->get('createCollection', function($data) {

		$eCustomer = new \selling\Customer();
		$cCustomer = new Collection();

		if(get_exists('group')) {

			$eGroup = \selling\GroupLib::getById(GET('group'))->validateProperty('farm', $data->eFarm);
			$cCustomer = \selling\CustomerLib::getByGroup($eGroup);

			if($cCustomer->notEmpty()) {
				$eCustomer = $cCustomer->first();
			}

		} else if(get_exists('customers')) {

			$cCustomer = \selling\CustomerLib::getByIds(GET('customers', 'array'));

			\selling\Customer::validateCreateSale($cCustomer, $data->eFarm);

			if($cCustomer->notEmpty()) {
				$eCustomer = $cCustomer->first();
			}

		} else if(get_exists('customer')) {

			$eCustomer = \selling\CustomerLib::getById(GET('customer'))->validateProperty('farm', $data->eFarm);

			if($eCustomer->notEmpty()) {
				$cCustomer[] = $eCustomer;
			}

		}

		if(
			$cCustomer->count() === 1 and
			$eCustomer['destination'] === \selling\Customer::COLLECTIVE
		) {
			throw new RedirectAction('/selling/sale:create?farm='.$data->eFarm['id'].'&customer='.$eCustomer['id'].'');
		}

		$data->e = new \selling\Sale([
			'farm' => $data->eFarm,
			'shop' => new \shop\Shop(),
			'shopDate' => new \shop\Date(),
			'origin' => \selling\Sale::SALE,
			'customer' => $eCustomer,
			'cCustomer' => $cCustomer
		]);

		$data->e->validate('canCreate');

		if($cCustomer->notEmpty()) {

			$data->e['type'] = $eCustomer['type'];
			$data->e['discount'] = $eCustomer['discount'];

			$data->e['hasVat'] = $data->e['farm']->getSelling('hasVat');
			$data->e['taxes'] = $data->e->getTaxesFromType();

			$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm'], index: 'id');

			$data->e['cProduct'] = \selling\ProductLib::getForSale($data->e['farm'], $data->e['type']);
			\selling\ProductLib::applyItemsForSale($data->e['cProduct'], $data->e);

			$data->cGroup = new Collection();

		} else {
			$data->cGroup = \selling\GroupLib::getByFarm($data->eFarm);
		}

		throw new \ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$eSaleReference = new \selling\Sale([
			'farm' => $data->eFarm,
			'origin' => \selling\Sale::SALE,
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
				\farm\FarmUi::urlSellingSales($data->eFarm, \farm\Farmer::ALL).'?success=selling:Sale::createdCollection' :
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
		$data->cPdf = \selling\PdfLib::getBySale($data->e);

		$data->e['invoice'] = \selling\InvoiceLib::getById($data->e['invoice'], properties: \selling\InvoiceElement::getSelection());
		$data->e['shopPoint'] = \shop\PointLib::getById($data->e['shopPoint']);

		if($data->e['shop']->notEmpty()) {
			$data->relativeSales = \shop\DateLib::getRelativeSales($data->e['shopDate'], $data->e);
		} else {
			$data->relativeSales = NULL;
		}
		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);

		throw new ViewAction($data, $data->eFarm->canSelling() ? ':salePlain' :  ':salePanel');

	})
	->read('/vente/{id}/devis', function($data) {

		$data->e->validate('acceptOrderForm');

		$content = \selling\PdfLib::getContentBySale($data->e, \selling\Pdf::ORDER_FORM);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = new \selling\PdfUi()->getFilename(\selling\Pdf::ORDER_FORM, $data->e['farm'], $data->e);

		throw new PdfAction($content, $filename);


	}, validate: ['canAccess'])
	->read('/vente/{id}/bon-livraison', function($data) {

		$data->e->validate('acceptDeliveryNote');

		$content = \selling\PdfLib::getContentBySale($data->e, \selling\Pdf::DELIVERY_NOTE);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = new \selling\PdfUi()->getFilename(\selling\Pdf::DELIVERY_NOTE, $data->e['farm'], $data->e);

		throw new PdfAction($content, $filename);


	}, validate: ['canAccess'])
	->read('generateOrderForm', function($data) {

		$data->e->validate('canManage');
		$data->e->acceptDocumentTarget($data->e['type']) ?: throw new FailAction('farm\Farm::disabled');
		$data->e->acceptGenerateOrderForm() ?: throw new FailAction('selling\Sale::generateOrderForm');

		$data->eFarm = $data->e['farm'];
		$data->eFarm->validateSellingComplete();

		$data->ePdf = \selling\PdfLib::getOne($data->e, \selling\Pdf::ORDER_FORM);

		if($data->e['orderFormPaymentCondition'] === NULL) {
			$data->e['orderFormPaymentCondition'] = $data->eFarm->getSelling('orderFormPaymentCondition');
		}

		throw new ViewAction($data);

	})
	->write('doGenerateDocument', function($data) {

		if($data->e['items'] === 0) {
			throw new FailAction('selling\Pdf::emptySale');
		}

		$data->e->acceptDocumentTarget($data->e['type']) ?: throw new FailAction('farm\Farm::disabled');

		$data->e['farm']->validateSellingComplete();

		$type = POST('type', [\selling\Pdf::DELIVERY_NOTE, \selling\Pdf::ORDER_FORM], fn() => throw new NotExpectedAction());

		if($data->e->canDocument($type) === FALSE) {
			throw new NotAllowedAction();
		}

		$fw = new FailWatch();

		switch($type) {

			case \selling\Pdf::DELIVERY_NOTE:

				$data->e->acceptGenerateDeliveryNote() ?: throw new FailAction('selling\Sale::generateDeliveryNote');

				break;

			case \selling\Pdf::ORDER_FORM :

				$data->e->acceptGenerateOrderForm() ?: throw new FailAction('selling\Sale::generateOrderForm');

				$data->e->build(['orderFormValidUntil', 'orderFormPaymentCondition'], $_POST);

				$fw->validate();

				\selling\Sale::model()
					->select(['orderFormValidUntil', 'orderFormPaymentCondition'])
					->update($data->e);

				break;

		};

		$data->ePdf = \selling\PdfLib::generate($type, $data->e);

		throw new ViewAction($data);
	})
	->write('doSendDocument', function($data) {

		$eFarm = \farm\FarmLib::getById($data->e['farm']);

		$data->type = POST('type', [\selling\Pdf::ORDER_FORM, \selling\Pdf::DELIVERY_NOTE], fn($value) => throw new NotExpectedAction('Invalid type \''.$value.'\''));

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
		$data->e['cPaymentMethod'] = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);

		throw new ViewAction($data);

	})
	->read('updateShop', function($data) {

		$data->e['cShop'] = \shop\ShopLib::getAroundByFarm($data->e['farm']);

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
	->write('doUpdatePaymentMethod', function($data) {

		$paymentMethodId = \payment\Method::POST('paymentMethod', 'id');
		$action = POST('action', 'string', 'update');
		$eMethod = \payment\MethodLib::getById($paymentMethodId)->validate('canUse', 'acceptManualUpdate');

		switch($action) {
			case 'update':
				$ePayment = \selling\PaymentLib::getById(POST('payment'));
				\selling\PaymentLib::updateBySaleAndMethod($data->e, eMethod: $eMethod, ePayment: $ePayment);
				break;

			case 'remove':
				\selling\PaymentLib::deleteBySaleAndMethod($data->e, eMethod: $eMethod);
				break;

			case 'add':
				\selling\PaymentLib::createByMarketSale($data->e, eMethod: $eMethod);
				break;

			default:
				throw new NotExpectedAction('Unknown action "'.$action.'"');

		}

		throw new ReloadAction();
	}, validate: ['canWrite', 'acceptUpdateMarketSalePayment'])
	->write('doFillPaymentMethod', function($data) {

		$paymentMethodId = \payment\Method::POST('paymentMethod', 'id');
		$eMethod = \payment\MethodLib::getById($paymentMethodId)->validate('canUse');

		\selling\PaymentLib::fill($data->e, eMethod: $eMethod);

		throw new ReloadAction();
	}, validate: ['canWrite', 'acceptUpdateMarketSalePayment'])
	->write('doDeleteOnlinePaymentMethod', function($data) {

		\selling\SaleLib::emptyPaymentMethod($data->e);

		throw new ReloadLayerAction();

	}, validate: ['canWrite', 'acceptEmptyOnlinePayment'])
	->doUpdateProperties('doUpdatePreparationStatus', ['preparationStatus'], function($data) {

		throw new ViewAction($data);

		}, validate: ['canUpdatePreparationStatus'])
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

		$data->e->build(['deliveredAt'], $_POST, new \Properties('create'));

		$fw->validate();

		$data->eSaleNew = \selling\SaleLib::duplicate($data->e);

		throw new RedirectAction(\selling\SaleUi::url($data->eSaleNew).'?success=series:Series::duplicated');
	})
	->write('doDelete', function($data) {

		$fw = new \FailWatch();

		if(
			$data->e->acceptDeleteStatus() === FALSE or
			$data->e->acceptDeletePaymentStatus() === FALSE
		) {
			Sale::fail('deletedNotDraft');
		} else if($data->e->acceptDeleteMarket() === FALSE) {
			Sale::fail('deletedMarket');
		} else if($data->e->acceptDeleteMarketSale() === FALSE) {
			Sale::fail('deletedMarketSale');
		}

		$fw->validate();

		\selling\SaleLib::delete($data->e);

		throw new RedirectAction(
			$data->e->isComposition() ?
				\selling\ProductUi::url($data->e['compositionOf']).'?success=selling:Product::deletedComposition' :
				\farm\FarmUi::urlSellingSalesAll($data->e['farm']).'?success=selling:Sale::deleted'
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
	->get('getExport', function($data) {

		$data->c->validate('canRemote');

		\selling\SaleLib::fillItems($data->c);

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
	->post('doUpdatePaymentMethodCollection', function($data) {

		$data->c->validate('canWrite', 'acceptUpdatePayment');

		$methodId = \payment\Method::POST('paymentMethod', 'id');
		$eMethod = \payment\MethodLib::getById($methodId);
		if($eMethod->notEmpty()) {
			$eMethod->validate('canUse', 'acceptManualUpdate');
		}

		\selling\SaleLib::updatePaymentMethodCollection($data->c, $eMethod);

		throw new ReloadAction('selling', 'Sale::paymentMethodUpdated');

	});

new \selling\PdfPage()
	->doDelete(fn($data) => throw new ReloadAction('selling', 'Pdf::deleted'), page: 'doDeleteDocument');

new \farm\FarmPage()
	->write('downloadLabels', function($data) {

		if(POST('checkSales', 'bool')) {

			$data->cSale = \selling\SaleLib::getForLabelsByIds($data->e, POST('sales', 'array'));

			if($data->cSale->empty()) {
				throw new RedirectAction(\farm\FarmUi::urlSellingSalesLabel($data->e).'?error=selling:Sale::downloadEmpty');
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
