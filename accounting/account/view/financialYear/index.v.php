<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Tous les exercices comptables de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/thirdParty/';

	$t->mainTitle = new \account\FinancialYearUi()->getManageTitle($data->eFarm, $data->cFinancialYearOpen);

	echo new \account\FinancialYearUi()->getManage($data->eFarm, $data->cFinancialYear);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->create($data->eFarm, $data->e);

});

new AdaptativeView('close', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Clôturer un exercice comptable");
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/thirdParty/';

	$t->mainTitle = new \account\FinancialYearUi()->getCloseTitle($data->eFarm);

	echo new \account\FinancialYearUi()->close(
		$data->eFarm,
		$data->e,
		$data->cOperationCharges,
		$data->cAccruedIncome,
		$data->cStock,
		$data->cAssetGrant
	);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->update($data->eFarm, $data->e);

});

?>
