<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \map\BedUi())->createCollection($data->season, $data->ePlot, $data->cGreenhouse);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \map\BedUi())->update($data->season, $data->e, $data->cPlot);

});

new AdaptativeView('swapSeries', function($data, PanelTemplate $t) {

	return (new \map\BedUi())->swapSeries($data->e, $data->season, $data->cZone);

});

new AdaptativeView('updateBedLineCollection', function($data, PanelTemplate $t) {

	return (new \map\BedUi())->updateBedLineCollection($data->season, $data->ePlot, $data->cBed);

});

new AdaptativeView('updateSizeCollection', function($data, PanelTemplate $t) {

	return (new \map\BedUi())->updateSizeCollection($data->season, $data->ePlot, $data->cBed);

});

new AdaptativeView('doUpdateSizeCollection', function($data, AjaxTemplate $t) {

	$t->js()->eval('Cartography.get("cartography-farm", instance => instance.reload())');
	$t->js()->closePanel('#panel-bed-size');

});

new AdaptativeView('doUpdateGreenhouseCollection', function($data, AjaxTemplate $t) {

	$t->js()->eval('Cartography.get("cartography-farm", instance => instance.reload())');

});

new AdaptativeView('updateSeasonCollection', function($data, PanelTemplate $t) {

	return (new \map\BedUi())->updateSeasonCollection($data->season, $data->ePlot, $data->cBed);

});

new AdaptativeView('doUpdateSeasonCollection', function($data, AjaxTemplate $t) {

	$t->js()->eval('Cartography.get("cartography-farm", instance => instance.reload())');
	$t->js()->closePanel('#panel-bed-season');

});

new AdaptativeView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->eval('Cartography.get("cartography-farm", instance => instance.reload())');
	$t->js()->closePanel('#panel-bed-update');

});

new AdaptativeView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->eval('Cartography.get("cartography-farm", instance => instance.reload())');

});
?>
