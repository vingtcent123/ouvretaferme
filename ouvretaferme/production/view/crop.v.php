<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \production\CropUi())->create($data->eSequence);
});

new JsonView('addPlant', function($data, AjaxTemplate $t) {
	$t->qs('#crop-create-content')->innerHtml((new \production\CropUi())->createContent($data->eSequence, $data->ccVariety));
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \production\CropUi())->update($data->e);
});

new JsonView('changePlant', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$t->ref('crop-field-variety')->outerHtml((new \production\CropUi())->getVarietyGroup($form, $data->e, $data->ccVariety, $data->cSlice));

});
?>
