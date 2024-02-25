<?php
new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \series\PlaceUi())->update($data->eSeries, $data->cZone, $data->cPlace, $data->search);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->qs('#series-soil')->outerHtml((new \series\SeriesUi())->updatePlace($data->eSeries, $data->cPlace));

});
?>
