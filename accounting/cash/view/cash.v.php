<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \cash\CashUi()->create($data->e);
});
