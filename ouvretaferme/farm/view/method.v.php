<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cMethod->makeArray(fn($eMethod) => \farm\MethodUi::getAutocomplete($eMethod));
	$t->push('results', $results);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \farm\MethodUi()->create($data->e);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('farm', 'Action::deleted');
	$t->ajaxReloadLayer();

});
?>
