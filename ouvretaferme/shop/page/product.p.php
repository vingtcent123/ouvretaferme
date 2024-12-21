<?php
(new \shop\ProductPage())
	->doDelete(function($data) {
		throw new ReloadAction('shop', 'Product::deleted');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->quick(['available', 'price']);

(new Page(function($data) {

		if(request_exists('date')) {

			$data->e = \shop\DateLib::getById(REQUEST('date'))->validate('canWrite');

		} else if(request_exists('catalog')) {

			$data->e = \shop\CatalogLib::getById(REQUEST('catalog'))->validate('canWrite');

		} else {
			throw new NotExpectedAction('Invalid source');
		}

	}))
	->get('createCollection', function($data) {

		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm'], index: 'id');

		$cProductSelling = \selling\ProductLib::getForShop($data->e['farm'], $data->e['type']);
		\shop\ProductLib::excludeExisting($data->e, $cProductSelling);

		$data->e['cProduct'] = $cProductSelling;

		throw new \ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$fw = new FailWatch();

		$products = POST('productsList', 'array', []);

		$cProductSelling = \selling\ProductLib::getForShop($data->e['farm'], $data->e['type']);
		$data->cProduct = \shop\ProductLib::prepareCollection($data->e, $cProductSelling, $products, $_POST);

		$fw->validate();

		\shop\ProductLib::createCollection($data->e, $data->cProduct);

		throw new ReloadAction('shop', 'Products::created');

	});
?>