<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \map\ZoneUi())->create($data->eFarm, $data->cZone);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \map\ZoneUi())->update($data->e, $data->cZone);

});

new AdaptativeView('getCartography', function($data, AjaxTemplate $t) {

	$h = (new \map\ZoneUi())->getHeader($data->e, $data->season);
	$h .= (new \map\PlotUi())->getPlotsForZone($data->e['cPlot'], $data->e, $data->season, $data->cGreenhouse, $data->ePlot);

	$t->qs('#cartography-zone')->innerHtml($h);

	if($data->e['coordinates'] === NULL) {
		$t->qs('#cartography-farm-container')->addClass('cartography-farm-container-hide');
	} else {
		$t->qs('#cartography-farm-container')->removeClass('cartography-farm-container-hide');
	}

});
?>
