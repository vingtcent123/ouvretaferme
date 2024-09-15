<?php
(new Page())
	->get('add', function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canWrite');

		throw new ViewAction($data);

	});

(new \farm\FarmPage())
	->update(page: 'updateNote')
	->doUpdateProperties('doUpdateNote', ['stockNotes'], function($data) {

		throw new ReloadAction();

	})
	->write('doNoteStatus', function($data) {

		\farm\FarmLib::updateStockNotesStatus($data->e, POST('enable', 'bool'));

		throw new ReloadAction();

	});

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

		$fw->validate();

		throw new ReloadAction('selling', 'Stock::updated');

	}, validate: ['canWrite', 'acceptStock']);
?>
