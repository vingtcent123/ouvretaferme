<?php

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()->createOrUpdate($data->eFarm, $data->eFarm['cFinancialYear'], $data->e, $data->cOperation, $data->cAmortizationDuration);

});

new AdaptativeView('attach', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()->attach($data->eFarm, $data->cOperation, $data->cAsset);

});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = [];

	foreach($data->cAsset as $eAsset) {

		$results[] = \asset\AssetUi::getAutocomplete($data->eFarm, $eAsset);

	}

	$t->push('results', $results);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()->createOrUpdate($data->eFarm, $data->eFarm['cFinancialYear'], $data->e, new Collection(), $data->cAmortizationDuration);

});

new AdaptativeView('/immobilisation/{id}/', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::view($data->eFarm, $data->e);

});

new AdaptativeView('dispose', function($data, PanelTemplate $t) {

	return new \asset\AssetUi()::dispose($data->eFarm, $data->eAsset);

});

new JsonView('getRecommendedDuration', function($data, AjaxTemplate $t) {

	$recommendation = new \asset\AssetUi()->getDurationRecommandation(POST('accountLabel'), $data->cAmortizationDuration);

	if($recommendation) {
		$t->qs('#amortization-duration-recommandation')->innerHtml($recommendation);
		$t->qs('#amortization-duration-recommandation')->removeHide();
	} else {
		$t->qs('#amortization-duration-recommandation')->hide();
	}

});


?>
