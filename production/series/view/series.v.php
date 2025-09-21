<?php
new AdaptativeView('/serie/{id}', function($data, FarmTemplate $t) {

	$t->title = s("Série {value}", $data->e['name']);
	$t->nav = 'cultivation';
	$t->subNav = 'series';

	$t->mainTitle =  new \series\SeriesUi()->getHeader($data->e);

	echo new \series\CultivationUi()->getList($data->e, $data->cSeriesPerennial, $data->cCultivation, $data->cTask, $data->cPlace, $data->cActionMain);
	echo new \series\TaskUi()->getTimeline($data->eFarm, $data->e, $data->cCultivation, $data->cTask);

	if($data->eFarm->hasFeatureTime()) {
		echo new \series\SeriesUi()->getWorkingTime($data->e, $data->cCultivation, $data->ccTask, $data->ccTaskHarvested);
	}

	echo new \series\SeriesUi()->getPhotos($data->e, $data->cPhoto);

});

new AdaptativeView('duplicate', function($data, PanelTemplate $t) {
	return new \series\SeriesUi()->duplicate($data->eFarm, $data->c, $data->cTaskMetadata, $data->hasPlaces);
});

new AdaptativeView('updateSeasonCollection', function($data, PanelTemplate $t) {
	return new \series\SeriesUi()->updateSeason($data->eFarm, $data->c);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \series\SeriesUi()->update($data->e);
});

new JsonView('updateComment', function($data, AjaxTemplate $t) {

	$t->qs('#series-comment')
		->outerHtml(new \series\SeriesUi()->getCommentField($data->e))
		->scrollTo(behavior: 'smooth');

	$t->qs('[name="comment"]')->focus();

});

new JsonView('getComment', function($data, AjaxTemplate $t) {
	$t->qs('#series-comment')->outerHtml(new \series\SeriesUi()->getComment($data->e));
});

new AdaptativeView('createFrom', function($data, PanelTemplate $t) {
	return new \series\SeriesUi()->createFrom($data->eFarm, $data->season);
});

new AdaptativeView('createFromPlant', function($data, PanelTemplate $t) {
	return new \series\SeriesUi()->createFromPlant($data->eFarm, $data->season, $data->eSeries, $data->eCultivation, $data->ePlant, $data->cAction);
});

new JsonView('addPlant', function($data, AjaxTemplate $t) {

	$t->qs('#series-create-plant input[name="index"]')->value($data->nextIndex);
	$t->qs('#series-create-plant-list')->insertAdjacentHtml('beforeend', new \series\SeriesUi()->addFromPlant($data->eSeries, $data->eCultivation, $data->ePlant, $data->nextIndex, $data->cAction));
	$t->qs('#series-create-add-plant input')->value('');
	$t->js()->eval('Series.showOrHideDeletePlant()');

});

new AdaptativeView('createFromSequence', function($data, PanelTemplate $t) {
	return new \series\SeriesUi()->createFromSequence($data->eFarm, $data->season, $data->eSequence, $data->cCultivation, $data->cFlow, $data->events);
});

new AdaptativeView('getTasksFromSequence', function($data, AjaxTemplate $t) {

	$t->qs('#series-create-tasks')->innerHtml(
		new \series\SeriesUi()->getTasksFromSequence($data->season, $data->eSequence, $data->events, $data->startYear, $data->startWeek)
	);

});
?>
