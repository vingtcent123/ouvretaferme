<?php
new AdaptativeView('/espece/{id@int}', function($data, FarmTemplate $t) {

	$t->title = $data->e['name'];
	$t->tab = 'plant';
	$t->subNav = (new \farm\FarmUi())->getCultivationSubNav($data->e['farm']);

	echo (new \plant\PlantUi())->display($data->e, $data->cItemTurnover);
	
	if($data->cCrop->notEmpty()) {
		echo '<h3>'.s("Itinéraires techniques").'</h3>';
		echo (new \production\SequenceUi())->getList($data->eFarm, $data->cCrop, $data->cActionMain);
	}

});

new AdaptativeView('analyzeSales', function($data, PanelTemplate $t) {
	return (new \selling\AnalyzeUi())->getPlantSales($data->e, $data->year, $data->cItemTurnover, $data->cItemCustomer, $data->cItemType, $data->cItemMonth, $data->cItemMonthBefore, $data->cItemWeek, $data->cItemWeekBefore, $data->search);
});

new AdaptativeView('analyzeTime', function($data, PanelTemplate $t) {
	return (new \series\AnalyzeUi())->getPlantTime($data->e, $data->year, $data->cPlantTimesheet, $data->cTimesheetByAction, $data->cTimesheetByUser, $data->cPlantMonth);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \plant\PlantUi())->create($data->eFarm, $data->cFamily);
});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	if(Route::getRequestedOrigin() === 'panel') {
		$t->js()->moveHistory(-1);
	} else {
		$t->ajaxReloadLayer();
	}

	$t->js()->success('plant', 'Plant::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \plant\PlantUi())->update($data->e);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->js()->success('plant', 'Plant::updated'.ucfirst($data->e['status']));
	$t->qs('#plant-switch-'.$data->e['id'])->toggleSwitch();
});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('plant', 'Plant::deleted');
	$t->ajaxReloadLayer();

});
?>
