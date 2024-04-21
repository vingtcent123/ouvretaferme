<?php
(new shop\PointPage())
	->getCreateElement(function($data) {

		$data->eShop = \shop\ShopLib::getById(INPUT('shop'));

		$type = \shop\Point::INPUT('type', 'type', fn() => throw new NotExpectedAction());

		if(
			$type === \shop\Point::HOME and
			\shop\PointLib::hasType($data->eShop, $type)
		) {
			throw new RedirectAction(\farm\FarmUi::urlSellingShop($data->eShop['farm']));
		}

		return new \shop\Point([
			'shop' => $data->eShop,
			'farm' => $data->eShop['farm'],
			'type' => $type
		]);

	})
	->create()
	->doCreate(function($data) {
		throw new ReloadAction('shop', 'Point::'.$data->e['type'].'.created');
	});

(new shop\PointPage())
	->applyElement(function($data, \shop\Point $e) {
		$e['shop'] = \shop\ShopLib::getById($e['shop']);
		$e['shop']['stripe'] = \payment\StripeLib::getByFarm($e['farm']);
	})
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('shop', 'Point::'.$data->e['type'].'.updated');
	})
	->doDelete(function($data) {
		throw new ReloadAction('shop', 'Point::'.$data->e['type'].'.deleted');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data));
?>
