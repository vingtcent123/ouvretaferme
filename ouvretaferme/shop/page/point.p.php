<?php
(new shop\PointPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$type = \shop\Point::INPUT('type', 'type', fn() => throw new NotExpectedAction());

		return new \shop\Point([
			'farm' => $data->eFarm,
			'type' => $type
		]);

	})
	->create()
	->doCreate(function($data) {
		throw new ReloadAction('shop', 'Point::'.$data->e['type'].'.created');
	});

(new shop\PointPage())
	->applyElement(function($data, \shop\Point $e) {
		$e['stripe'] = \payment\StripeLib::getByFarm($e['farm']);
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
