<?php
new \selling\GridPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		return new \selling\Grid([
			'farm' => $data->eFarm,
		]);

	})
	->create(function($data) {

		$data->e['product'] = get_exists('product') ?
			\selling\ProductLib::getById(GET('product'))->validateProperty('farm', $data->eFarm):
			new \selling\Product();

		$data->e['group'] = new \selling\CustomerGroup();
		$data->e['customer'] = new \selling\Customer();

		if(get_exists('customer')) {
			$data->e['customer'] = \selling\CustomerLib::getById(GET('customer'))->validateProperty('farm', $data->eFarm);
			$data->e['type'] = $data->e['customer']['type'];
		} else if(get_exists('group')) {
			$data->e['group'] = \selling\CustomerGroupLib::getById(GET('group'))->validateProperty('farm', $data->eFarm);
			$data->e['type'] = $data->e['group']['type'];
		}

		if(
			$data->e['group']->empty() and
			$data->e['customer']->empty()
		) {

			$data->e['cGroup'] = \selling\CustomerGroupLib::getByFarm($data->eFarm);

		}

		throw new ViewAction($data);

	})
	->doCreate(fn() => throw new ReloadAction());

new \selling\GridPage()
	->applyElement(function($data, \selling\Grid $e) {

		\selling\Product::model()
			->select('farm', 'status')
			->get($e['product']);

		$e['product']->validate('canWrite');

		if($e['priceInitial'] !== NULL) {
			$e['priceDiscount'] = $e['price'];
		}
	})
	->quick(['price' => ['price', 'priceDiscount']]);

new \selling\GridPage()
	->update(function($data) {

		$data->e['cCategory'] = \farm\CategoryLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new ViewAction($data))
	->doDelete(fn() => throw new ReloadAction());

new \selling\ProductPage()
	->write('doDeleteByProduct', function($data) {

		\selling\GridLib::deleteByProduct($data->e);

		throw new ReloadLayerAction();

	});

new \selling\CustomerPage()
	->write('doDeleteByCustomer', function($data) {

		\selling\GridLib::deleteByCustomer($data->e);

		throw new ReloadLayerAction();

	});

new \selling\CustomerGroupPage()
	->write('doDeleteByGroup', function($data) {

		\selling\GridLib::deleteByGroup($data->e);

		throw new ReloadLayerAction();

	});
?>
