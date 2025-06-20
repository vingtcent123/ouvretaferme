<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Tous les exercices comptables de {value}", $data->eFarm['name']);
	$t->tab = 'settings';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eFarm);

	$t->mainTitle = new \account\FinancialYearUi()->getManageTitle($data->eFarm, $data->cFinancialYearOpen);

	echo new \account\FinancialYearUi()->getManage($data->eFarm, $data->cFinancialYear);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->create($data->eFarm, $data->e);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->update($data->eFarm, $data->e);

});

?>
