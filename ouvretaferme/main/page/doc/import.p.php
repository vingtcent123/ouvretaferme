<?php
(new Page(function($data) {

		$cFarmer = \farm\FarmerLib::getOnline();

		$data->eFarm = $cFarmer->notEmpty() ? $cFarmer->first()['farm'] : new \farm\Farm();

	}))
	->get('series', fn($data) => throw new ViewAction($data))
	->get('products', fn($data) => throw new ViewAction($data));
?>
