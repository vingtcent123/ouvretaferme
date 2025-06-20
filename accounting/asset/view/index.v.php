<?php
new AdaptativeView('view', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::view($data->eFarm, $data->eAsset);

});

new AdaptativeView('dispose', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::dispose($data->eFarm, $data->eFinancialYear, $data->eAsset);

});
