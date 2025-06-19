<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \shop\RangeUi()->create($data->e);

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->js()->success('shop', 'Range::created');

});

new AdaptativeView('dissociate', function($data, PanelTemplate $t) {

	return new \shop\RangeUi()->dissociate($data->e);

});
?>
