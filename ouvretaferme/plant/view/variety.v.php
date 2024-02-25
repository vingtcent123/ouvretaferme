<?php
new AdaptativeView('index', function($data, PanelTemplate $t) {
	return (new \plant\VarietyUi())->displayByPlant($data->e, $data->ePlant, $data->cVariety, $data->cSupplier);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \plant\VarietyUi())->create($data->eFarm, $data->ePlant, $data->cSupplier);
});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	if(Route::getRequestedOrigin() === 'panel') {
		$t->js()->moveHistory(-1);
	} else {
		$t->ajaxReloadLayer();
	}

	$t->js()->success('plant', 'Variety::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \plant\VarietyUi())->update($data->e);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('plant', 'Variety::deleted');
	$t->ajaxReloadLayer();

});
?>
