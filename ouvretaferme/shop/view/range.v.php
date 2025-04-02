<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \shop\RangeUi()->create($data->e);

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->js()->success('shop', 'Range::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \shop\RangeUi()->update($data->e);

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->success('shop', 'Range::updated');
	$t->js()->moveHistory(-1);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('shop', 'Range::deleted');
	$t->ajaxReloadLayer();

});
?>
