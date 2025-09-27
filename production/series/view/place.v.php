<?php
new AdaptativeView('updateModal', function($data, PanelTemplate $t) {
	return new \series\PlaceUi()->update($data->eFarm, $data->source, $data->e, $data->cZone, $data->search);
});

new JsonView('doUpdateModal', function($data, AjaxTemplate $t) {

	switch($data->source) {

		case 'series' :
			$t->js()->moveHistory(-1);
			$t->qs('#series-soil')->outerHtml(new \series\SeriesUi()->updatePlace($data->e, $data->cPlace));
			break;

		case 'task' :
			$t->js()->moveHistory(-1);
			$t->ajaxReload(purgeLayers: FALSE);
			break;

	}

});

new JsonView('updateSoil', function($data, AjaxTemplate $t) {

	$t->qs('#zone-container')->outerHtml(new \map\ZoneUi()
		->setUpdate($data->eSeries)
		->getPlan($data->eFarm, $data->cZone, new \map\Zone(), $data->season));

	$t->js()->eval('SeriesSelector.edit('.$data->e['id'].')');

});

new JsonView('doUpdateSoil', function($data, AjaxTemplate $t) {


		$uiZone = new \map\ZoneUi();

		if($data->eCultivationSelected->notEmpty()) {
			$uiZone->setUpdate($data->eCultivationSelected['series']);
		}

		echo $uiZone->getPlan($data->eFarm, $data->cZone, $data->eZoneSelected, $data->season);

});

new JsonView('doDeleteSoil', function($data, AjaxTemplate $t) {

	$t->qs('#series-selector-list')->outerHtml(new \series\SeriesUi()->getSelectorSeries($data->ccCultivation));
	$t->qs('#zone-container')->outerHtml(new \map\ZoneUi()->getPlan($data->eFarm, $data->cZone, new \map\Zone(), $data->season));

	$t->js()->eval('SeriesSelector.select('.$data->e['id'].')');

});
?>
