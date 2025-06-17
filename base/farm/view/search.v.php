<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cFarm->makeArray(fn($eFarm) => \farm\FarmUi::getAutocomplete($eFarm));

	$t->push('results', $results);

});