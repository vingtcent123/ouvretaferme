<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \cash\RegisterUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \cash\RegisterUi()->update($data->e);
});