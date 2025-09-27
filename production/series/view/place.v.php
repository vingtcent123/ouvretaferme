<?php
new AdaptativeView('updateModal', function($data, PanelTemplate $t) {
	return new \series\PlaceUi()->update($data->e['farm'], $data->source, $data->e, $data->cZone, $data->search);
});

new JsonView('updateCultivation', function($data, AjaxTemplate $t) {

	$t->push('plan', new \map\ZoneUi()
		->setUpdate($data->e)
		->getPlan($data->e['farm'], $data->cZone, new \map\Zone(), $data->e['season']));

	$t->push('search', new \series\PlaceUi()->getPlaceSearch($data->e, $data->search));

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	switch($data->source) {

		case 'cultivation' :
			$t->qs('#series-selector-list')->outerHtml(new \series\SeriesUi()->getSelectorSeries($data->ccCultivation));
			$t->qs('#zone-container')->outerHtml(new \map\ZoneUi()->getPlan($data->e['farm'], $data->cZone, new \map\Zone(), $data->e['season']));

			$t->js()->success('series', 'Series::updatedSoil');
			$t->js()->eval('SeriesSelector.select('.$data->eCultivation['id'].')');
			$t->js()->eval('SeriesSelector.restoreFilter()');
			break;

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
?>
