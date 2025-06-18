<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Tous les exercices comptables de {value}", $data->eCompany['name']);
	$t->tab = 'settings';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eCompany);

	$t->mainTitle = new \accounting\FinancialYearUi()->getManageTitle($data->eCompany, $data->cFinancialYearOpen);

	echo new \accounting\FinancialYearUi()->getManage($data->eCompany, $data->cFinancialYear);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \accounting\FinancialYearUi()->create($data->eCompany, $data->e);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \accounting\FinancialYearUi()->update($data->eCompany, $data->e);

});

?>
