<?php
new AdaptativeView('createCollection', function($data, PanelTemplate $t) {
	return (new \series\CommentUi())->createCollection($data->cTask);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \series\CommentUi())->update($data->e);
});
?>
