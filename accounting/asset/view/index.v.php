<?php
new AdaptativeView('view', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::view($data->eCompany, $data->eAsset);

});

new AdaptativeView('dispose', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::dispose($data->eCompany, $data->eFinancialYear, $data->eAsset);

});
