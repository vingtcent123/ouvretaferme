<?php
(new \selling\SalePage())
	->applyElement(function($data, \selling\Sale $eSale) {

		$eSale->validate('canWriteItems');

	})
	->read('add', fn($data) => throw new ViewAction($data))
	->read('one', function($data) {

		$data->eProduct = \selling\ProductLib::getById(POST('product'));

		if($data->eProduct->notEmpty()) {

			if(\selling\ItemLib::isCompatible($data->e, $data->eProduct) === FALSE) {
				throw new NotExpectedAction('Sale not compatible with Product');
			}

		}

		$data->e['farm']['selling'] = \selling\ConfigurationLib::getByFarm($data->e['farm']);

		$data->eItem = new \selling\Item([
			'farm' => $data->e['farm'],
			'sale' => $data->e,
			'product' => $data->eProduct,
			'vatRate' => Setting::get('selling\vatRates')[$data->e['farm']['selling']['defaultVat']],
			'quality' => $data->eProduct->empty() ? new \plant\Quality() : $data->eProduct['quality'],
			'customer' => $data->e['customer'],
			'locked' => \selling\Item::PRICE,
		]);

		if($data->eProduct->notEmpty() and $data->e['customer']->notEmpty()) {
			$data->eGrid = \selling\GridLib::getOne($data->e['customer'], $data->eProduct);
		} else {
			$data->eGrid = new \selling\Grid();
		}

		throw new ViewAction($data);

	}, method: 'post')
	->write('doAdd', function($data) {

		$fw = new FailWatch();

		$data->cItem = \selling\ItemLib::build($data->e, $_POST);

		$fw->validate();

		\selling\ItemLib::createCollection($data->cItem);

		throw new BackAction('selling', 'Item::created');

	});

(new \selling\ItemPage())
	->applyElement(function($data, \selling\Item $e) {

		$e->validate('canWrite');

	})
	->quick(['packaging', 'number', 'unitPrice', 'vatRate', 'price', 'description'])
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('selling', 'Item::updated');
	})
	->doDelete(function($data) {
		throw new ReloadAction('selling', 'Item::deleted');
	});

(new Page())
	->get('getDeliveredAt', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canSelling');

		$data->date = GET('date');
		$data->type = GET('type', [\selling\Customer::PRO, \selling\Customer::PRIVATE], NULL);
		$data->cSale = \selling\SaleLib::getByDeliveredDay($data->eFarm, $data->date, $data->type);
		$data->ccItemProduct = \selling\ItemLib::getProductsBySales($data->cSale);
		$data->ccItemSale = \selling\ItemLib::getBySales($data->cSale);

		throw new ViewAction($data);

	});
?>
