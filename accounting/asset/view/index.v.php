<?php
new AdaptativeView('/asset/{id}/', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::view($data->eFarm, $data->e);

});

new AdaptativeView('dispose', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::dispose($data->eFarm, $data->eAsset);

});
