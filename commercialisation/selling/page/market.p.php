<?php
new \selling\SalePage()
	->read('/vente/{id}/marche', function($data) {

		$data->e->checkMarketSelling();

		$data->nItems = \selling\SaleLib::countItems($data->e);
		$data->ccSale = \selling\SaleLib::getByParent($data->e);

		throw new ViewAction($data);

	}, validate: ['canWrite'])
	->read('/vente/{id}/marche/vente/{subId}', function($data) {

		$data->e->checkMarketSelling();

		$data->ccSale = \selling\SaleLib::getByParent($data->e);
		$data->cItemMarket = \selling\SaleLib::getItems($data->e);

		$data->eSale = \selling\SaleLib::getById(GET('subId'), \selling\Sale::getSelection() + [
			'createdBy' => ['firstName', 'lastName', 'vignette'],
			'cPayment' => \selling\PaymentLib::delegateBySale(),
		]);

		if($data->eSale->empty()) {
			throw new RedirectAction(\selling\SaleUi::urlMarket($data->e));
		}

		$data->eSale->validate('canWrite');

		if(
			$data->eSale->isMarketSale() === FALSE or
			$data->eSale['marketParent']['id'] !== $data->e['id']
		) {
			throw new NotExpectedAction('Parent mismatch');
		}

		$data->cItemSale = \selling\SaleLib::getItems($data->eSale, index: 'product');

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);
		$data->eFarmer = $data->e['farm']->getFarmer();

		throw new ViewAction($data);

	}, validate: ['canWrite'])
	->read('/vente/{id}/marche/articles', function($data) {

		$data->e->checkMarketSelling();

		$data->ccSale = \selling\SaleLib::getByParent($data->e);
		$data->cItemMarket = \selling\SaleLib::getItems($data->e);

		throw new ViewAction($data);


	}, validate: ['canWrite'])
	->write('doClose', function($data) {

		\selling\MarketLib::close($data->e);

		throw new RedirectAction(\selling\SaleUi::urlMarket($data->e).'?success=selling:Market::closed');

	})
	->write('doPaidMarketSale', function($data) {

		$fw = new FailWatch();

		\selling\MarketLib::doPaidMarketSale($data->e);

		$fw->validate();

		throw new ReloadAction();

	}, validate: ['isMarketSale', 'acceptStatusDelivered', 'acceptUpdatePayment'])
	->write('doNotPaidMarketSale', function($data) {

		if($data->e['customer']->empty()) {
			throw new NotExpectedAction('Missing customer');
		}

		$eSaleMarket = $data->e['marketParent'];

		$fw = new FailWatch();

		\selling\MarketLib::doNotPaidMarketSale($data->e);

		$fw->validate();

		throw new RedirectAction(\selling\SaleUi::urlMarket($eSaleMarket).'?success=selling:Market::saleExcluded');

	}, validate: ['isMarketSale', 'acceptStatusDelivered', 'acceptUpdatePayment'])
	->write('doUpdatePrices', function($data) {

		$cItem = \selling\MarketLib::checkNewPrices($data->e, POST('unitPrice', 'array', []));

		\selling\MarketLib::updateMarketPrices($data->e, $cItem);

		throw new RedirectAction(\selling\SaleUi::urlMarket($data->e).'/articles?success=selling:Market::pricesUpdated');


	}, validate: ['canWrite', 'isMarketSelling'])
	->write('doUpdateSale', function($data) {

		$data->e->validate('isMarketSale');

		if($data->e['preparationStatus'] !== \selling\Sale::DRAFT) {
			throw new \FailAction('selling\Sale::market.status');
		}

		$data->eSaleMarket = \selling\SaleLib::getById($data->e['marketParent']);
		$data->eSaleMarket->checkMarketSelling();

		$fw = new FailWatch();

		$cItemSale = \selling\ItemLib::checkNewItems($data->e, $_POST);

		$fw->validate();

		\selling\ItemLib::updateSaleCollection($data->e, $cItemSale);

		\selling\PaymentLib::fillOnlyMarketPayment($data->e);

		$data->e = \selling\SaleLib::getById($data->e, \selling\Sale::getSelection() + [
			'createdBy' => ['firstName', 'lastName', 'vignette'],
			'cPayment' => \selling\PaymentLib::delegateBySale(),
		]);

		$data->cItemMarket = \selling\SaleLib::getItems($data->eSaleMarket);
		$data->cItemSale = \selling\SaleLib::getItems($data->e, index: 'product');

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);

		$data->eFarmer = $data->e['farm']->getFarmer();

		throw new ViewAction($data);

	})
	->read('/vente/{id}/marche/ventes', function($data) {

		$data->e->checkMarketSelling();

		$data->ccSaleLast = \selling\MarketLib::getLast($data->e);

		$data->hours = \selling\MarketLib::getByHour($data->e);

		$data->ccSale = \selling\SaleLib::getByParent($data->e);
		$data->cSale = $data->ccSale
			->linearize()
			->sort(['id' => SORT_DESC]);

		$data->cItem = \selling\AnalyzeLib::getSaleProducts($data->e, FALSE);
		$data->cItemStats = \selling\MarketLib::getItemStats($data->cSale);

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);

		throw new ViewAction($data);


	}, validate: ['canWrite'])
	->write('doCreateSale', function($data) {

		$data->eSale = \selling\SaleLib::createFromMarket($data->e);

		throw new RedirectAction(\selling\SaleUi::urlMarket($data->e).'/vente/'.$data->eSale['id']);


	}, validate: ['canWrite', 'isMarketSelling'])
	->doDelete(function($data) {
		throw new RedirectAction(\selling\SaleUi::urlMarket($data->e['marketParent']).'?success=selling:Sale::deleted');
	})
	->read('sendTicket', function($data) {

		$data->e['cItem'] = \selling\SaleLib::getItems($data->e);

		$data->e->validate('canSendTicket');

		throw new ViewAction($data);

	})
	->write('doSendTicket', function($data) {

		$data->e['cItem'] = \selling\SaleLib::getItems($data->e);

		$data->e->validate('canSendTicket');

		$fw = new \FailWatch();

		$email = POST('email');
		if(\Filter::check('email', $email) === FALSE) {
			Sale::fail('ticket.email');
		}

		$fw->validate();

		\selling\MarketLib::sendTicket($data->e, $email);

		throw new ReloadAction('selling', 'Sale::ticket.send');

	});
?>
