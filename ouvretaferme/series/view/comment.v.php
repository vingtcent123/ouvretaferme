<?php
new AdaptativeView('createCollection', function($data, PanelTemplate $t) {
	return (new \series\CommentUi())->createCollection($data->cTask);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \series\CommentUi())->update($data->e);
});

new AdaptativeView('doDelete', function($data, AjaxTemplate $t) {

	$t->qs('#panel-task')->setAttribute('data-close', 'reload');
	$t->ajaxReloadLayer();

});
?>
