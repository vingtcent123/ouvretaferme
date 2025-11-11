<?php

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()->create($data->eFarm, $data->e);

});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = [];

	foreach($data->cAsset as $eAsset) {

		$results[] = \asset\AssetUi::getAutocomplete($data->eFarm['id'], $eAsset);

	}

	//$results[] = \account\AccountUi::getAutocompleteCreate($data->eFarm);

	$t->push('results', $results);

});
?>
