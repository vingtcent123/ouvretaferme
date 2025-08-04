<?php
new \shop\ProductPage()
	->update(function($data) {

		$data->e['cCustomerLimit'] = \selling\CustomerLib::getByIds($data->e['limitCustomers'], sort: ['lastName' => SORT_ASC, 'firstName' => SORT_ASC]);
		$data->e['cCustomerExclude'] = \selling\CustomerLib::getByIds($data->e['excludeCustomers'], sort: ['lastName' => SORT_ASC, 'firstName' => SORT_ASC]);

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {
		throw new ReloadAction('shop', 'Product::updated');
	})
	->doDelete(function($data) {
		throw new ReloadAction('shop', 'Product::deleted');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->quick(['available', 'price', 'packaging']);

(new Page(function($data) {

		if(request_exists('date')) {

			$data->e = \shop\DateLib::getById(REQUEST('date'), \shop\Date::getSelection() + ['shop' => ['shared']])->validate('canWrite', 'acceptNotShared');

		} else if(request_exists('catalog')) {

			$data->e = \shop\CatalogLib::getById(REQUEST('catalog'))->validate('canWrite');

		} else {
			throw new NotExpectedAction('Invalid source');
		}

	}))
	->get('createCollection', function($data) {

		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm'], index: 'id');

		$cProductSelling = \selling\ProductLib::getForSale($data->e['farm'], $data->e['type']);
		\shop\ProductLib::excludeExisting($data->e, $cProductSelling);

		$data->e['cProduct'] = $cProductSelling;

		throw new \ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$fw = new FailWatch();

		$products = POST('products', 'array', []);

		$cProductSelling = \selling\ProductLib::getForSale($data->e['farm'], $data->e['type']);
		$data->cProduct = \shop\ProductLib::prepareCollection($data->e, $cProductSelling, $products, $_POST);

		$fw->validate(onKo: fn() => $fw->has('Product::empty') ? NULL : \shop\Product::fail('createCollectionError'));

		\shop\ProductLib::createCollection($data->e, $data->cProduct);

		throw new ReloadAction('shop', 'Products::created');

	});
?>