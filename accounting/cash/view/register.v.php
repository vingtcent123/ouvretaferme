<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \selling\CustomerUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \selling\CustomerUi()->update($data->e);
});