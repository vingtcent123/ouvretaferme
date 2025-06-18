<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cCompany->makeArray(fn($eCompany) => \company\CompanyUi::getAutocomplete($eCompany));

	$t->push('results', $results);

});