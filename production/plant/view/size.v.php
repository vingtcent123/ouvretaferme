<?php
new AdaptativeView('index', function($data, PanelTemplate $t) {
	return new \plant\SizeUi()->displayByPlant($data->e, $data->ePlant, $data->cSize);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \plant\SizeUi()->create($data->eFarm, $data->ePlant);
});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	if(Route::getRequestedOrigin() === 'panel') {
		$t->js()->moveHistory(-1);
	} else {
		$t->ajaxReloadLayer();
	}

	$t->js()->success('plant', 'Size::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \plant\SizeUi()->update($data->e);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('plant', 'Size::deleted');
	$t->ajaxReloadLayer();

});
?>
