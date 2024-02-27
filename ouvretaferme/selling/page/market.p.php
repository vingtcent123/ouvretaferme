<?php
(new \selling\SalePage())
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
			'createdBy' => ['firstName', 'lastName', 'vignette']
		]);

		if($data->eSale->empty()) {
			throw new RedirectAction(\selling\SaleUi::urlMarket($data->e));
		}

		$data->eSale->validate('canWrite');

		if(
			$data->eSale['marketParent']->empty() or
			$data->eSale['marketParent']['id'] !== $data->e['id']
		) {
			throw new NotExpectedAction('Parent mismatch');
		}

		$data->cItemSale = \selling\SaleLib::getItems($data->eSale, index: ['product']);

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
	->write('doUpdatePrices', function($data) {

		$cItem = \selling\MarketLib::checkNewPrices($data->e, POST('unitPrice', 'array', []));

		\selling\MarketLib::updateMarketPrices($data->e, $cItem);

		throw new RedirectAction(\selling\SaleUi::urlMarket($data->e).'/articles?success=selling:Market::pricesUpdated');


	})
	->write('doUpdateSale', function($data) {

		$data->e->checkMarketSelling();

		$data->eSale = \selling\SaleLib::getById(POST('subId'));

		if($data->eSale['preparationStatus'] !== \selling\Sale::DRAFT) {
			throw new NotExpectedAction('Invalid sale status');
		}

		$cItemSale = \selling\MarketLib::checkNewItems($data->e, $data->eSale, $_POST);

		\selling\MarketLib::updateSaleItems($data->eSale, $cItemSale);

		$data->eSale = \selling\SaleLib::getById(POST('subId'), \selling\Sale::getSelection() + [
			'createdBy' => ['firstName', 'lastName', 'vignette']
		]);

		$data->cItemMarket = \selling\SaleLib::getItems($data->e);
		$data->cItemSale = \selling\SaleLib::getItems($data->eSale, index: ['product']);

		throw new ViewAction($data);


	})
	->read('/vente/{id}/marche/ventes', function($data) {

		$data->e->checkMarketSelling();

		$data->ccSale = \selling\SaleLib::getByParent($data->e);

		throw new ViewAction($data);


	}, validate: ['canWrite'])
	->write('doCreateSale', function($data) {

		$data->eSale = \selling\SaleLib::createFromMarket($data->e);

		throw new RedirectAction(\selling\SaleUi::urlMarket($data->e).'/vente/'.$data->eSale['id']);


	})
	->doDelete(function($data) {
		throw new RedirectAction(\selling\SaleUi::urlMarket($data->e['marketParent']).'?success=selling:Sale::deleted');
	});
?>
