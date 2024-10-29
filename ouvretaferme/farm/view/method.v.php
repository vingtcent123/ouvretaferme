<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \farm\MethodUi())->create($data->e);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('farm', 'Action::deleted');
	$t->ajaxReloadLayer();

});

new AdaptativeView('analyzeTime', function($data, PanelTemplate $t) {
	return (new \farm\AnalyzeUi())->getActionTime($data->e, $data->eCategory, $data->year, $data->cActionTimesheet, $data->cTimesheetMonth, $data->cTimesheetMonthBefore, $data->cTimesheetUser);
});
?>
