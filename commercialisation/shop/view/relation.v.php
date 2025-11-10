<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \shop\RelationUi()->create($data->e);
});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cRelation->makeArray(fn($eRelation) => \shop\RelationUi::getAutocomplete($eRelation));
	$t->push('results', $results);

});
?>
