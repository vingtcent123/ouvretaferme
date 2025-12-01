<?php
new shop\PointPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$type = \shop\Point::INPUT('type', 'type', fn() => throw new NotExpectedAction());
		$mode = \shop\Point::INPUT('mode', '?mode', fn() => throw new NotExpectedAction());

		if($type === \shop\Point::HOME and $mode === NULL) {
			new NotExpectedAction();
		}

		if($type === \shop\Point::PLACE and $mode !== NULL) {
			new NotExpectedAction();
		}

		return new \shop\Point([
			'farm' => $data->eFarm,
			'type' => $type,
			'mode' => $mode
		]);

	})
	->create()
	->doCreate(function($data) {
		throw new ReloadAction('shop', 'Point::'.$data->e['type'].'.created');
	});

new shop\PointPage()
	->applyElement(function($data, \shop\Point $e) {
		$e['stripe'] = \payment\StripeLib::getByFarm($e['farm']);
	})
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('shop', 'Point::'.$data->e['type'].'.updated');
	})
	->doDelete(function($data) {
		throw new ReloadAction('shop', 'Point::'.$data->e['type'].'.deleted');
	});
?>
