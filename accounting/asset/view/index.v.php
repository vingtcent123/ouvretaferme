<?php

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()->createOrUpdate($data->eFarm, $data->cFinancialYear, $data->e);

});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = [];

	foreach($data->cAsset as $eAsset) {

		$results[] = \asset\AssetUi::getAutocomplete($data->eFarm['id'], $eAsset);

	}

	//$results[] = \account\AccountUi::getAutocompleteCreate($data->eFarm);

	$t->push('results', $results);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()->createOrUpdate($data->eFarm, $data->cFinancialYear, $data->e);

});

new AdaptativeView('/immobilisation/{id}/', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::view($data->eFarm, $data->e);

});

new AdaptativeView('dispose', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::dispose($data->eFarm, $data->eAsset);

});


?>
