<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les interventions de {value}", $data->eFarm['name']);
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm);

	$t->mainTitle = (new \farm\ActionUi())->getManageTitle($data->eFarm);
	echo (new \farm\ActionUi())->getManage($data->eFarm, $data->cAction, $data->cCategory);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \farm\ActionUi())->create($data->eFarm, $data->cCategory);

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->js()->success('farm', 'Action::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \farm\ActionUi())->update($data->e);

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
	return (new \farm\AnalyzeUi())->getActionTime($data->e, $data->eCategory, $data->year, $data->cActionTimesheet, $data->cTimesheetMonth, $data->cTimesheetMonthBefore, $data->cTimesheetUser);
});
?>
