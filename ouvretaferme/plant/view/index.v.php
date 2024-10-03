<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cPlant->makeArray(fn($ePlant) => \plant\PlantUi::getAutocomplete($ePlant));
	$results[] = \plant\PlantUi::getAutocompleteCreate($data->eFarm);

	$t->push('results', $results);

});
?>
