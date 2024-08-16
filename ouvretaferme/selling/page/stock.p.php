<?php
(new \selling\ProductPage())
	->read('increment', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->read('decrement', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->write('doCrement', function($data) {

		$sign = POST('sign', ['+', '-'], fn() => throw new NotExpectedAction('Bad sign'));

		$fw = new FailWatch();

		$eStock = new \selling\Stock();
		$eStock->build(['newValue'], $_POST);

		$fw->validate();

		match($sign) {
			'+' => \selling\StockLib::increment($data->e, $eStock['newValue']),
			'-' => \selling\StockLib::decrement($data->e, $eStock['newValue'])
		};

		throw new ReloadAction('selling', 'Stock::updated');

	}, validate: ['canWrite', 'acceptStock']);
?>
