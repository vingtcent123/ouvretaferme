<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \selling\GridUi()->create($data->e);
});
?>
