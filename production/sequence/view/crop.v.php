<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \sequence\CropUi()->create($data->eSequence);
});

new JsonView('addPlant', function($data, AjaxTemplate $t) {
	$t->qs('#crop-create-content')->innerHtml(new \sequence\CropUi()->createContent($data->eSequence, $data->ccVariety, $data->cAction));
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \sequence\CropUi()->update($data->e, $data->cAction);
});

new JsonView('changePlant', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$t->ref('crop-field-variety')->outerHtml(new \sequence\CropUi()->getVarietyGroup($form, $data->e, $data->ccVariety, $data->cSlice));

});
?>
