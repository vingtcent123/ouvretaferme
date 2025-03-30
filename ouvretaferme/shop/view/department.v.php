<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \shop\DepartmentUi()->create($data->eShop);

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->js()->success('shop', 'Department::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \shop\DepartmentUi()->update($data->e);

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->success('shop', 'Department::updated');
	$t->js()->moveHistory(-1);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('shop', 'Department::deleted');
	$t->ajaxReloadLayer();

});
?>
