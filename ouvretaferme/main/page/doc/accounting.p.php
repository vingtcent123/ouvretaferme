<?php
new Page(function($data) {

	$cFarmer = \farm\FarmerLib::getOnline();
	$cFarm = \farm\Farm::model()
		->select('id', 'hasAccounting')
		->whereId('IN', $cFarmer->getColumnCollection('farm')->getIds())
		->getCollection();

	$data->eFarm = $cFarm->notEmpty() ? $cFarm->first() : new \farm\Farm();

	foreach($cFarm as $eFarm) {
		if($eFarm->usesAccounting()) {
			$data->eFarm = $eFarm;
			break;
		}

		if($eFarm->hasAccounting()) {
			$data->eFarm = $eFarm;
		}
	}


})
	->get('index', fn($data) => throw new ViewAction($data));
?>
