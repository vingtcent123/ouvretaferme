<?php
(new \selling\SalePage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \selling\Sale([
			'from' => \selling\Sale::USER,
			'farm' => $data->eFarm
		]);

	})
	->create(function($data) {

		$data->e->merge([
			'cShop' => \shop\ShopLib::getAroundByFarm($data->eFarm),
			'market' => GET('market', 'bool'),
			'customer' => get_exists('customer') ? \selling\CustomerLib::getById(GET('customer')) : new \selling\Customer()
		]);

		throw new \ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\selling\SaleUi::url($data->e).'?success=Sale::created');
	});

(new \selling\SalePage())
	->read('/vente/{id}', function($data) {

		$data->eFarm = $data->e['farm'];

		if($data->e['marketParent']->notEmpty()) {
			throw new NotExpectedAction('Market sale');
		}

		\farm\FarmerLib::register($data->eFarm);

		$data->cItem = \selling\SaleLib::getItems($data->e);
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

		throw new ViewAction($data);

	})
	->read('/vente/{id}/devis', function($data) {

		$data->e->validate('canOrderForm');

		$content = \selling\PdfLib::getContentBySale($data->e, \selling\Pdf::ORDER_FORM);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = $data->e->getOrderForm().'-'.str_replace('-', '', $data->e['deliveredAt']).'-'.$data->e['customer']['name'].'.pdf';

		throw new PdfAction($content, $filename);


	}, validate: ['canAccess'])
	->read('/vente/{id}/bon-livraison', function($data) {

		$data->e->validate('canDeliveryNote');

		$content = \selling\PdfLib::getContentBySale($data->e, \selling\Pdf::DELIVERY_NOTE);

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = $data->e->getDeliveryNote().'-'.str_replace('-', '', $data->e['deliveredAt']).'-'.$data->e['customer']['name'].'.pdf';

		throw new PdfAction($content, $filename);


	}, validate: ['canAccess'])
	->read('generateOrderForm', function($data) {

		$data->e->validate('canManage');
		$data->e->canGenerateOrderForm() ?: throw new FailAction('selling\Sale::generateOrderForm');

		$data->eFarm = $data->e['farm'];
		$data->eFarm->hasFeatureDocument($data->e['type']) ?: throw new FailAction('farm\Farm::disabled');

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);
		$data->eFarm['selling']->isComplete() ?: throw new FailAction('selling\Configuration::notComplete', ['farm' => $data->eFarm]);

		$data->ePdf = \selling\PdfLib::getOne($data->e, \selling\Pdf::ORDER_FORM);

		if($data->e['orderFormPaymentCondition'] === NULL) {
			$data->e['orderFormPaymentCondition'] = $data->eFarm['selling']['orderFormPaymentCondition'];
		}

		throw new ViewAction($data);

	})
	->write('doGenerateDocument', function($data) {

		$data->e->validate('canManage');

		if($data->e['items'] === 0) {
			throw new FailAction('selling\Pdf::emptySale');
		}

		$data->e['farm']->hasFeatureDocument($data->e['type']) ?: throw new FailAction('farm\Farm::disabled');

		$data->e['farm']['selling'] = \selling\ConfigurationLib::getByFarm($data->e['farm']);
		$data->e['farm']['selling']->isComplete() ?: throw new FailAction('selling\Configuration::notComplete', ['farm' => $data->e['farm']]);

		$type = POST('type', [\selling\Pdf::DELIVERY_NOTE, \selling\Pdf::ORDER_FORM], fn() => throw new NotExpectedAction());

		$fw = new FailWatch();

		switch($type) {

			case \selling\Pdf::DELIVERY_NOTE:

				$data->e->canGenerateDeliveryNote() ?: throw new FailAction('selling\Sale::generateDeliveryNote');

				break;

			case \selling\Pdf::ORDER_FORM :

				$data->e->canGenerateOrderForm() ?: throw new FailAction('selling\Sale::generateOrderForm');

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
		$eFarm['selling'] = \selling\ConfigurationLib::getByFarm($eFarm);

		$data->type = POST('type', [\selling\Pdf::ORDER_FORM, \selling\Pdf::DELIVERY_NOTE], fn($value) => throw new NotExpectedAction('Invalid type \''.$value.'\''));

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

		$data->e['farm']['selling'] = \selling\ConfigurationLib::getByFarm($data->e['farm']);
		$data->e['cShop'] = \shop\ShopLib::getAroundByFarm($data->e['farm']);

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {
		throw new ReloadAction('selling', 'Sale::updated');
	})
	->read('updateShop', function($data) {

		$data->e['cShop'] = \shop\ShopLib::getAroundByFarm($data->e['farm']);

		throw new ViewAction($data);

	}, validate: ['canWrite', 'canAssociateShop'])
	->write('doUpdateShop', function($data) {
		
		$fw = new FailWatch();

		$from = POST('from', [\selling\Sale::SHOP, \selling\Sale::USER], fn() => throw new FailAction('selling\Sale::from.check'));

		$data->e['from'] = $from;

		match($from) {
			\selling\Sale::USER => $data->e->validate('canDissociateShop'),
			\selling\Sale::SHOP => $data->e->validate('canAssociateShop')
		};

		$data->e->build(['shopDate'], $_POST, for: 'update');

		$fw->validate();

		\selling\SaleLib::update($data->e, ['from', 'shop', 'shopDate']);

		throw new ReloadAction('selling', 'Sale::updated');

	})
	->read('updateCustomer', function($data) {

		throw new ViewAction($data);

	}, validate: ['canUpdateCustomer'])
	->write('doUpdateCustomer', function($data) {

		$fw = new FailWatch();

		$data->e->build(['customer'], $_POST, for: 'update');

		$fw->validate();

		\selling\SaleLib::updateCustomer($data->e, $data->e['customer']);

		throw new ReloadAction('selling', 'Sale::customerUpdated');

	}, validate: ['canUpdateCustomer'])
	->doUpdateProperties('doUpdatePaymentMethod', ['paymentMethod'], fn() => throw new ReloadAction(), validate: ['canWrite'])
	->doUpdateProperties('doUpdatePreparationStatus', ['preparationStatus'], fn() => throw new ReloadAction(), validate: ['canWritePreparationStatus'])
	->read('duplicate', function($data) {

		if($data->e->canDuplicate() === FALSE) {
			throw new NotExpectedAction('Can duplicate');
		}

		throw new ViewAction($data);

	})
	->write('doDuplicate', function($data) {

		if($data->e->canDuplicate() === FALSE) {
			throw new NotExpectedAction('Can duplicate');
		}

		$fw = new \FailWatch();

		$data->e->build(['deliveredAt'], $_POST, for: 'create');

		$fw->validate();

		$data->eSaleNew = \selling\SaleLib::duplicate($data->e);

		throw new RedirectAction(\selling\SaleUi::url($data->eSaleNew).'?success=series:Series::duplicated');
	})
	->write('doDelete', function($data) {

		$fw = new \FailWatch();

		if(
			$data->e->canDeleteStatus() === FALSE or
			$data->e->canDeletePaymentStatus() === FALSE
		) {
			Sale::fail('deletedNotDraft');
		} else if($data->e->canDeleteMarket() === FALSE) {
			Sale::fail('deletedMarket');
		} else if($data->e->canDeleteMarketSale() === FALSE) {
			Sale::fail('deletedMarketSale');
		}

		$fw->validate();

		\selling\SaleLib::delete($data->e);

		throw new RedirectAction(\farm\FarmUi::urlSellingSalesAll($data->e['farm']).'?success=selling:Sale::deleted');
	});

(new Page(function($data) {

		$data->c = \selling\SaleLib::getByIds(REQUEST('ids', 'array'));

		\selling\Sale::validateBatch($data->c);

		$data->eFarm = $data->c->first()['farm'];

	}))
	->post('doExportCollection', function($data) {

		$data->c->validate('canRead');

		$content = \selling\PdfLib::build('/selling/sale:getExport?ids[]='.implode('&ids[]=', $data->c->getIds()));
		$filename = 'sales.pdf';

		throw new PdfAction($content, $filename);

	})
	->get('getExport', function($data) {

		\selling\SaleLib::fillItems($data->c);

		$data->cItem = \selling\ItemLib::getSummaryBySales($data->c);

		throw new ViewAction($data);

	})
	->post('doUpdateCancelCollection', function($data) {

		$data->c->validate('canWrite', 'canStatusCancel');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::CANCELED);

		throw new ReloadAction();

	})
	->post('doUpdateDeliveredCollection', function($data) {

		$data->c->validate('canWrite', 'canStatusDelivered');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::DELIVERED);

		throw new ReloadAction();

	})
	->post('doUpdateConfirmedCollection', function($data) {

		$data->c->validate('canWrite', 'canStatusConfirmed');

		\selling\SaleLib::updatePreparationStatusCollection($data->c, \selling\Sale::CONFIRMED);

		throw new ReloadAction();

	})
	->post('doDeleteCollection', function($data) {

		$data->c->validate('canDelete', 'canDeleteSale');

		\selling\SaleLib::deleteCollection($data->c);

		throw new ReloadAction();

	});

(new \selling\PdfPage())
	->doDelete(fn($data) => throw new ReloadAction('selling', 'Pdf::deleted'), page: 'doDeleteDocument');

(new \farm\FarmPage())
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

		$data->content = \selling\PdfLib::buildLabels($data->e, $data->cSale);

		$filename = $data->cSale->empty() ?
			'labels-empty.pdf' :
			'labels-'.implode('-', $data->cSale->getIds()).'.pdf';

		throw new PdfAction($data->content, $filename);

	});
?>
