<?php
(new \selling\ProductPage())
	->read('history', function($data) {

		$data->cStock = \selling\StockLib::getByProduct($data->e);

		throw new ViewAction($data);

	})
	->read('update', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->read('increment', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->read('decrement', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->write('doUpdate', function($data) {

		$sign = post_exists('sign') ? POST('sign', ['+', '-'], fn() => throw new NotExpectedAction('Bad sign')) : NULL;

		$fw = new FailWatch();

		$eStock = new \selling\Stock();
		$eStock->build(['newValue', 'comment'], $_POST);

		$fw->validate();

		match($sign) {
			'+' => \selling\StockLib::increment($data->e, $eStock),
			'-' => \selling\StockLib::decrement($data->e, $eStock),
			NULL => \selling\StockLib::set($data->e, $eStock)
		};

		throw new ReloadAction('selling', 'Stock::updated');

	}, validate: ['canWrite', 'acceptStock']);
?>
