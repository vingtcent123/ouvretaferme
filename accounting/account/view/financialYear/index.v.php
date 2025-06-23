<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Tous les exercices comptables de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/thirdParty/';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eFarm);

	$t->mainTitle = new \account\FinancialYearUi()->getManageTitle($data->eFarm, $data->cFinancialYearOpen);
	$t->mainTitleClass = 'hide-lateral-down';

	echo new \account\FinancialYearUi()->getManage($data->eFarm, $data->cFinancialYear);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->create($data->eFarm, $data->e);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->update($data->eFarm, $data->e);

});

?>
