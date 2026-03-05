<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \receipts\LineUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \receipts\LineUi()->update($data->e);
});
