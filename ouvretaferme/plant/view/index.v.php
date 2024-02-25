<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cPlant->makeArray(fn($ePlant) => \plant\PlantUi::getAutocomplete($ePlant));

	$t->push('results', $results);

});
?>
