<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cUser->makeArray(fn($eUser) => \user\UserUi::getAutocomplete($eUser));

	$t->push('results', $results);

});