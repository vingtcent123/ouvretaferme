<?php
(new Page(function($data) {

		$cFarmer = \farm\FarmerLib::getOnline();

		$data->eFarm = $cFarmer->notEmpty() ? $cFarmer->first()['farm'] : new \farm\Farm();

	}))
	->get('/doc/', fn($data) => throw new ViewAction($data))
	->get('design', fn($data) => throw new ViewAction($data))
	->get('help', fn($data) => throw new ViewAction($data));
?>
