<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les interventions de {value}", $data->eFarm['name']);
	$t->nav = 'settings-production';

	$t->mainTitle = new \farm\ActionUi()->getManageTitle($data->eFarm);
	echo new \farm\ActionUi()->getManage($data->eFarm, $data->actions, $data->cAction, $data->cCategory, $data->search);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \farm\ActionUi()->create($data->eFarm, $data->cCategory);

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->js()->success('farm', 'Action::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \farm\ActionUi()->update($data->e);

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->success('farm', 'Action::updated');
	$t->js()->moveHistory(-1);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('farm', 'Action::deleted');
	$t->ajaxReloadLayer();

});

new AdaptativeView('analyzeTime', function($data, PanelTemplate $t) {
	return new \farm\AnalyzeUi()->getTime($data->eAction, $data->eCategory, $data->year, $data->cTimesheetTarget, $data->cTimesheetMonth, $data->cTimesheetMonthBefore, $data->cTimesheetWeek, $data->cTimesheetWeekBefore, $data->cTimesheetUser);
});
?>
