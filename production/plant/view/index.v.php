<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cPlant->makeArray(fn($ePlant) => \plant\PlantUi::getAutocomplete($ePlant));

	if($data->hasNew) {
	//	$results[] = \plant\PlantUi::getAutocompleteCreate($data->eFarm);
	}

	$t->push('results', $results);

});
?>
