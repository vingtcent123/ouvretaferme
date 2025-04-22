<?php
(new Page(function($data) {

		$cFarmer = \farm\FarmerLib::getOnline();

		$data->eFarm = $cFarmer->notEmpty() ? $cFarmer->first()['farm'] : new \farm\Farm();

	}))
	->get('shared', fn($data) => throw new ViewAction($data));
?>
