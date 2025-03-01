<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \series\CultivationUi()->create($data->eSeries);
});

new JsonView('addPlant', function($data, AjaxTemplate $t) {
	$t->qs('#cultivation-create-content')->innerHtml(new \series\CultivationUi()->createContent($data->eSeries, $data->eCultivation, $data->cAction));
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \series\CultivationUi()->update($data->e, $data->cAction);
});

new AdaptativeView('harvest', function($data, PanelTemplate $t) {
	return new \series\CultivationUi()->harvest($data->e, $data->cTask);
});

new JsonView('changePlant', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$t->ref('crop-field-variety')->outerHtml(new \production\CropUi()->getVarietyGroup($form, $data->e, $data->ccVariety, $data->cSlice));

});
?>
