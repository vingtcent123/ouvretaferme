<?php
(new \selling\ProductPage())
	->read('increment', fn($data) => throw new ViewAction($data), validate: ['canWrite', 'acceptStock'])
	->write('doIncrement', function($data) {

		$data->cGrid = \selling\GridLib::prepareByProduct($data->e, $_POST);

		\selling\GridLib::updateGrid($data->cGrid);

		throw new ViewAction();

	}, validate: ['canWrite', 'acceptStock']);
?>
