<?php
new AdaptativeView('index', function($data, PanelTemplate $t) {
	return (new \gallery\PhotoUi())->displayPanel($data->e);
});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->success('gallery', 'Photo.created');

	if($data->e['task']->notEmpty()) {
		$t->ajaxReloadLayer();
	} else {
		$t->ajaxReload();
	}

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new gallery\PhotoUi())->update($data->e);
});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->package('gallery')->deletePhoto($data->e['id']);

	$t->js()->moveHistory(-1);

});
?>
