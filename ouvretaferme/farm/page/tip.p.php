<?php
(new Page())
	->get('index', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');

		\farm\FarmerLib::register($data->eFarm);

		$data->tip = \farm\TipLib::pickPosition($data->eUserOnline);
		$data->tipNavigation = 'next';

		throw new ViewAction($data);

	})
	->get('click', function($data) {

		$tip = GET('id', \farm\TipLib::getList());

		if($tip === NULL) {
			throw new NotExpectedAction('Invalid tip');
		}

		\farm\TipLib::changeStatus($data->eUserOnline, $tip, 'clicked');

		throw new RedirectAction(GET('redirect'));


	})
	->get('close', function($data) {

		$tip = GET('id', \farm\TipLib::getList());

		if($tip === NULL) {
			throw new NotExpectedAction('Invalid tip');
		}

		\farm\TipLib::changeStatus($data->eUserOnline, $tip, 'closed');

		throw new ViewAction($data);

	});
?>
