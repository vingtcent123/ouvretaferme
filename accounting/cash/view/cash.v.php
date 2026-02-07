<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \cash\CashUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \cash\CashUi()->update($data->e);
});

new AdaptativeView('updateBalance', function($data, PanelTemplate $t) {
	return new \cash\CashUi()->updateBalance($data->e);
});
