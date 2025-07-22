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

new AdaptativeView('open', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Créer le bilan d'ouverture");
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/financialYear/:open';

	$t->mainTitle = new \account\FinancialYearUi()->getOpenTitle($data->eFarm);

	echo new \account\FinancialYearUi()->open(
		$data->eFarm,
		$data->e,
		$data->eFinancialYearPrevious,
		$data->cOperation,
		$data->cDeferral,
	);

});

new AdaptativeView('close', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Clôturer un exercice comptable");
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/financialYear/:close?id='.$data->e['id'];

	$t->mainTitle = new \account\FinancialYearUi()->getCloseTitle($data->eFarm);

	echo new \account\FinancialYearUi()->close(
		$data->eFarm,
		$data->e,
		$data->cOperationToDefer,
		$data->cStock,
		$data->cAssetGrant
	);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->update($data->eFarm, $data->e);

});

?>
