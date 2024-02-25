<?php
new AdaptativeView('/outil/{id@int}', function($data, PanelTemplate $t) {
	return (new \farm\ToolUi())->display($data->eFarm, $data->eTool);
});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cTool->makeArray(fn($eTool) => \farm\ToolUi::getAutocomplete($eTool));
	$t->push('results', $results);

});

new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = ($data->routineName ? \farm\RoutineUi::getProperty($data->routineName, 'pageTitle')($data->eFarm) : s("Le petit matériel de {value}", $data->eFarm['name']));
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm, ($data->routineName ? \farm\RoutineUi::getProperty($data->routineName, 'title') : s("Petit matériel")));

	echo (new \farm\ToolUi())->manage($data->eFarm, $data->routineName, $data->tools, $data->cTool, $data->eToolNew, $data->cActionUsed, $data->search);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \farm\ToolUi())->create($data->e);
});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	if(Route::getRequestedOrigin() === 'panel') {
		$t->js()->moveHistory(-1);
	} else {
		$t->ajaxReloadLayer();
	}

	$t->js()->success('farm', 'Tool::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \farm\ToolUi())->update($data->e, $data->routines);

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('farm', 'Tool::deleted');
	$t->ajaxReloadLayer();

});

new AdaptativeView('getRoutinesField', function($data, AjaxTemplate $t) {

	if($data->routines === []) {
		$t->push('field', '');
	} else {
		$t->push('field', (new \farm\RoutineUi())->getFields(array_keys($data->routines), $data->eTool));
	}


});
?>
