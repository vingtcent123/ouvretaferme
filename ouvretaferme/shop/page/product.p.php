<?php
(new \shop\ProductPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$data->eShop = \shop\ShopLib::getById(INPUT('shop'))->validateProperty('farm', $data->eFarm);

		return new \shop\Date([
			'farm' => $data->eFarm,
			'shop' => $data->eShop
		]);

	})
	->create(function($data) {

		\farm\FarmerLib::register($data->eFarm);

		$data->eDate = \shop\DateLib::getById(GET('date'));

		$cProductSelling = \selling\ProductLib::getForShop($data->eFarm);
		$cProduct = \shop\ProductLib::getByDate($data->eDate, onlyActive: FALSE);

		foreach($cProduct as $eProduct) {
			$cProductSelling->offsetUnset($eProduct['product']['id']);
		}

		$data->cProduct = $cProductSelling;

		throw new \ViewAction($data);

	}, page: '/ferme/{farm}/boutique/{shop}/date/{date}/product:create');

(new \shop\ProductPage())
	->doDelete(function($data) {
		throw new ReloadAction('shop', 'Product::deleted');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->quick(['stock', 'price']);
?>