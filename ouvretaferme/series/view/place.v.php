<?php
new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \series\PlaceUi())->update($data->source, $data->e, $data->cZone, $data->cPlace, $data->search);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	switch($data->source) {

		case 'series' :
			$t->js()->moveHistory(-1);
			$t->qs('#series-soil')->outerHtml((new \series\SeriesUi())->updatePlace($data->e, $data->cPlace));
			break;

		case 'task' :
			$t->js()->moveHistory(-1);
			$t->ajaxReload(purgeLayers: FALSE);
			break;

	}

});
?>
