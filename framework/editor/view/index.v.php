<?php
new JsonView('configureVideo', function($data, AjaxTemplate $t) {
	$t->pushPanel((new \editor\EditorUi())->getVideoConfigure());
});

new JsonView('configureGrid', function($data, AjaxTemplate $t) {
	$t->pushPanel((new \editor\EditorUi())->getGridConfigure());
});

new JsonView('configureMedia', function($data, AjaxTemplate $t) {

	$t->pushPanel((new \editor\EditorUi())->getMediaConfigure($data->instanceId, $data->url, $data->xyz, $data->title, $data->link, $data->figureSize));

});
?>
