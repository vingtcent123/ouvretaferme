<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \journal\StockUi()->create($data->eFarm, $data->e, $data->eFinancialYear);

});

new AdaptativeView('set', function($data, PanelTemplate $t) {

	return new \journal\StockUi()->set($data->eFarm, $data->e, $data->eFinancialYear);

});
