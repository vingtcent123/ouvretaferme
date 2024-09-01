<?php
(new \selling\ProductPage())
	->read('update', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->read('increment', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->read('decrement', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->write('doUpdate', function($data) {

		$sign = post_exists('sign') ? POST('sign', ['+', '-'], fn() => throw new NotExpectedAction('Bad sign')) : NULL;

		$fw = new FailWatch();

		$eStock = new \selling\Stock();
		$eStock->build(['newValue'], $_POST);

		$fw->validate();

		match($sign) {
			'+' => \selling\StockLib::increment($data->e, $eStock['newValue']),
			'-' => \selling\StockLib::decrement($data->e, $eStock['newValue']),
			NULL => \selling\StockLib::set($data->e, $eStock['newValue'])
		};

		throw new ReloadAction('selling', 'Stock::updated');

	}, validate: ['canWrite', 'acceptStock']);
?>
